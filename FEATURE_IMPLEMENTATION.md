# Système d’Approbation des Congés à Plusieurs Niveaux – Plan d’Implémentation

Ce document décrit la conception technique et les détails d’implémentation du workflow d’approbation des congés à 2 niveaux demandé.

## 1. Modifications du Schéma de Base de Données

### A. Ajouter un Responsable aux Départements

Afin de prendre en charge le niveau d’approbation « Chef de département », il est nécessaire d’associer un responsable à chaque département.

```php
// database/migrations/xxxx_xx_xx_add_manager_id_to_departments_table.php
public function up()
{
    Schema::table('departments', function (Blueprint $table) {
        $table->foreignId('manager_id')
              ->nullable()
              ->after('branch_id')
              ->constrained('users')
              ->nullOnDelete();
    });
}
```

### B. Créer la Table des Approbations de Congés

Cette table permet de suivre le cycle de vie d’une demande de congé à travers les différentes étapes.

```php
// database/migrations/xxxx_xx_xx_create_leave_approvals_table.php
public function up()
{
    Schema::create('leave_approvals', function (Blueprint $table) {
        $table->id();
        $table->foreignId('leave_application_id')->constrained()->cascadeOnDelete();
        $table->integer('stage')->comment('1 : Chef de département, 2 : RH');
        $table->foreignId('approver_id')->constrained('users');
        $table->enum('status', ['pending', 'approved', 'rejected']);
        $table->text('comments')->nullable();
        $table->timestamp('actioned_at')->nullable();
        $table->timestamps();
    });
}
```

### C. Créer la Table des Journaux d’Audit

Pour tracer toutes les actions à des fins de conformité et de sécurité.

```php
// database/migrations/xxxx_xx_xx_create_audit_logs_table.php
public function up()
{
    Schema::create('audit_logs', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
        $table->string('action'); // ex. : 'leave_approved', 'leave_rejected'
        $table->string('module'); // ex. : 'LeaveManagement'
        $table->unsignedBigInteger('target_id')->nullable(); // ex. : leave_application_id
        $table->json('details')->nullable(); // Instantané des données
        $table->string('ip_address')->nullable();
        $table->string('user_agent')->nullable();
        $table->timestamps();
    });
}
```

### D. Mettre à Jour la Table des Demandes de Congés

Ajout de champs pour supporter l’état du workflow.

```php
// database/migrations/xxxx_xx_xx_update_leave_applications_workflow.php
public function up()
{
    Schema::table('leave_applications', function (Blueprint $table) {
        // Améliorer l’énumération du statut si la BD le supporte,
        // sinon gérer via la logique applicative
        // 'pending' sera traité comme 'pending_approval_1' ou 'pending_approval_2' selon l’étape
        $table->integer('current_stage')->default(1)->after('status');
        $table->boolean('is_completed')->default(false)->after('current_stage');
    });
}
```

## 2. Implémentation Backend

### A. Modèles

**Mises à jour de LeaveApplication.php**

```php
public function approvals()
{
    return $this->hasMany(LeaveApproval::class);
}

public function department()
{
    // Accéder au département via l’employé
    return $this->employee->department(); 
    // Nécessite dans le modèle User :
    // public function employee() { return $this->hasOne(Employee::class, 'user_id'); }
}
```

**LeaveApproval.php**

```php
class LeaveApproval extends Model
{
    protected $fillable = [
        'leave_application_id',
        'stage',
        'approver_id',
        'status',
        'comments',
        'actioned_at'
    ];
    
    public function application()
    {
        return $this->belongsTo(LeaveApplication::class);
    }
    
    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
```

**AuditLog.php**

```php
class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'module',
        'target_id',
        'details',
        'ip_address',
        'user_agent'
    ];
    
    protected $casts = ['details' => 'array'];
}
```

### B. Couche Service (Machine à États)

**LeaveApprovalService.php**

```php
namespace App\Services;

use App\Models\LeaveApplication;
use App\Models\LeaveApproval;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;

class LeaveApprovalService
{
    public function approve(LeaveApplication $leave, User $approver, string $comments = null)
    {
        return DB::transaction(function () use ($leave, $approver, $comments) {
            $currentStage = $leave->current_stage;
            
            // Validation : vérifier que l’approbateur est autorisé pour cette étape
            $this->validateApprover($leave, $approver, $currentStage);

            // Enregistrer l’approbation
            LeaveApproval::create([
                'leave_application_id' => $leave->id,
                'stage' => $currentStage,
                'approver_id' => $approver->id,
                'status' => 'approved',
                'comments' => $comments,
                'actioned_at' => now(),
            ]);

            // Transition d’état
            if ($currentStage === 1) {
                $leave->update(['current_stage' => 2]);
                // TODO : envoyer une notification aux RH
            } elseif ($currentStage === 2) {
                $leave->update(['status' => 'approved', 'is_completed' => true]);
                // Finalisation : déduire le solde, créer la présence
                $leave->createAttendanceRecords();
            }

            // Journal d’audit
            AuditLog::create([
                'user_id' => $approver->id,
                'action' => 'leave_approved_stage_' . $currentStage,
                'module' => 'LeaveManagement',
                'target_id' => $leave->id,
                'details' => ['comments' => $comments],
                'ip_address' => request()->ip()
            ]);

            return $leave;
        });
    }

    public function reject(LeaveApplication $leave, User $approver, string $comments = null)
    {
        // Implémentation du rejet
        // (met immédiatement le statut à rejeté et is_completed = true)
    }

    private function validateApprover($leave, $approver, $stage)
    {
        if ($stage === 1) {
            // Vérifier que l’approbateur est le responsable du département
            $deptManagerId = $leave->employee->department->manager_id;
            if ($approver->id !== $deptManagerId && !$approver->hasRole('Admin')) {
                abort(403, 'Seul le chef de département peut approuver à l’étape 1');
            }
        } elseif ($stage === 2) {
            // Vérifier que l’approbateur est RH
            if (
                !$approver->hasRole('HR Generalist') &&
                !$approver->hasRole('Admin')
            ) {
                abort(403, 'Seuls les RH peuvent approuver à l’étape 2');
            }
        }
    }
}
```

## 3. Composants Frontend (React/Inertia)

### A. Composant de Statut d’Approbation

Affiche la progression actuelle de la demande de congé.

```tsx
// resources/js/Components/Leave/ApprovalTracker.tsx
import { CheckCircle, Clock, XCircle } from 'lucide-react';

export default function ApprovalTracker({ currentStage, status, approvals }) {
  const steps = [
    { level: 1, label: 'Chef de département' },
    { level: 2, label: 'RH Généraliste' }
  ];

  return (
    <div className="flex items-center space-x-4">
      {steps.map((step) => {
        const approval = approvals.find(a => a.stage === step.level);
        const isCompleted = step.level < currentStage || status === 'approved';
        const isCurrent = step.level === currentStage && status === 'pending';
        
        return (
            <div key={step.level} className="flex flex-col items-center">
                <div className={`p-2 rounded-full ${
                    isCompleted ? 'bg-green-100' :
                    isCurrent ? 'bg-blue-100' : 'bg-gray-100'
                }`}>
                    {approval?.status === 'rejected' ? <XCircle className="text-red-600" /> :
                     isCompleted ? <CheckCircle className="text-green-600" /> : 
                     <Clock className={isCurrent ? "text-blue-600" : "text-gray-400"} />}
                </div>
                <span className="text-xs mt-1">{step.label}</span>
            </div>
        );
      })}
    </div>
  );
}
```

### B. Modale d’Approbation

Interface permettant aux responsables d’approuver ou rejeter avec commentaires.

```tsx
// resources/js/Components/Leave/ApprovalModal.tsx
import { useState } from 'react';
import { useForm } from '@inertiajs/react';

export default function ApprovalModal({ leaveId, onClose }) {
    const { data, setData, post, processing } = useForm({
        action: 'approve', // ou 'reject'
        comments: ''
    });

    const handleSubmit = (action) => {
        setData('action', action);
        post(route('leave.approve', leaveId), {
            onSuccess: () => onClose()
        });
    };

    return (
        <Dialog>
            <DialogContent>
                <DialogHeader>Approuver la demande de congé</DialogHeader>
                <Textarea 
                    value={data.comments} 
                    onChange={e => setData('comments', e.target.value)} 
                    placeholder="Ajouter des commentaires..."
                />
                <div className="flex justify-end gap-2 mt-4">
                    <Button variant="destructive" onClick={() => handleSubmit('reject')}>
                        Rejeter
                    </Button>
                    <Button variant="default" onClick={() => handleSubmit('approve')}>
                        Approuver
                    </Button>
                </div>
            </DialogContent>
        </Dialog>
    );
}
```

## 4. Stratégie de Tests

### A. Tests Unitaires

* **LeaveApprovalServiceTest** :

  * Tester que `approve()` fait passer l’étape 1 → 2.
  * Tester que `approve()` à l’étape 2 marque la demande comme approuvée.
  * Tester que `validateApprover()` retourne une erreur 403 pour les utilisateurs non autorisés.

### B. Tests d’Intégration

* **LeaveFlowTest** :

  * Créer un employé, un chef de département et des utilisateurs RH.
  * Soumettre une demande de congé en tant qu’employé.
  * Se connecter en tant que chef de département → Approuver. Vérifier l’état en base (étape 2).
  * Se connecter en tant que RH → Approuver. Vérifier l’état en base (statut approuvé, solde déduit).

## 5. Documentation

* **API** :

  * `POST /api/leave-applications/{id}/approve`
  * `POST /api/leave-applications/{id}/reject`
* **Rôles** :

  * S’assurer que les rôles « Chef de département » et « RH Généraliste » sont initialisés dans `RoleSeeder.php`.
