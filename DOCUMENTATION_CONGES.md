# Système de Gestion des Congés Multi-Niveaux - Documentation Technique

## 📋 Vue d'ensemble

Ce document décrit l'implémentation complète d'un système de gestion des congés de qualité hospitalière avec un workflow d'approbation multi-niveaux (jusqu'à 4 niveaux) et une gestion granulaire des soldes de congés.

## 🎯 Objectifs du Projet

1. **Workflow d'approbation à 4 niveaux** : Manager 1 → Manager 2 (optionnel) → RH → Directeur
2. **Gestion précise des soldes** : Suivi détaillé des allocations, utilisations, reports et ajustements
3. **Historique d'audit complet** : Traçabilité de tous les mouvements de congés
4. **Calcul intelligent des jours** : Exclusion des weekends et jours fériés
5. **Interface utilisateur intuitive** : Visualisation claire pour RH et employés

---

## 🗄️ Phase 1 : Base de Données & Configuration

### Nouvelles Tables

#### `leave_settings`
Table de configuration globale pour les paramètres de congés (Super Admin).

```sql
- id
- key (string) : clé du paramètre
- value (text) : valeur du paramètre
- description (text) : description
- created_at, updated_at
```

**Paramètres clés :**
- `default_annual_stock` : Stock annuel par défaut (ex: 20 jours)
- `carryover_enabled` : Activation du report
- `carryover_max_years` : Nombre d'années de report maximum
- `carryover_max_days` : Nombre maximum de jours reportables

#### `leave_movements`
Table d'audit pour tous les mouvements de soldes de congés.

```sql
- id
- leave_balance_id (FK)
- type (enum: 'earned', 'used', 'adjusted', 'carried_forward')
- amount (decimal)
- reason (text)
- created_by (FK users)
- created_at, updated_at
```

### Modifications de Tables Existantes

#### `departments`
Ajout des validateurs pour le workflow d'approbation.

```sql
+ validator1_id (FK users, nullable)
+ validator2_id (FK users, nullable)
```

#### `leave_balances`
Amélioration du suivi des soldes.

```sql
+ initial_leave_balance (decimal)
+ carry_over_years (integer)
```

---

## ⚙️ Phase 2 : Logique Backend

### Services Créés

#### `LeaveApprovalService`
**Fichier :** `app/Services/LeaveApprovalService.php`

Gère le workflow d'approbation à 4 étapes dynamiques.

**Méthodes principales :**

```php
// Détermine l'étape suivante basée sur la configuration du département
public function getNextStage(LeaveApplication $leave): ?int

// Traite l'approbation et passe à l'étape suivante
public function approve(LeaveApplication $leave, User $approver, ?string $comments = null): bool

// Traite le rejet et arrête le workflow
public function reject(LeaveApplication $leave, User $approver, string $reason): bool

// Vérifie si l'utilisateur peut approuver à l'étape actuelle
public function canApprove(LeaveApplication $leave, User $user): bool
```

**Workflow d'approbation :**

1. **Étape 1 - Manager 1** (Obligatoire)
   - Validateur principal du département
   - Première validation managériale

2. **Étape 2 - Manager 2** (Optionnel)
   - Basé sur `validator2_id` du département
   - Sauté si non configuré

3. **Étape 3 - RH** (Obligatoire)
   - Validation des ressources humaines
   - Vérification des soldes et politiques

4. **Étape 4 - Directeur** (Obligatoire)
   - Approbation finale
   - Déduction automatique du solde

#### `LeaveCalculationService`
**Fichier :** `app/Services/LeaveCalculationService.php`

Calcule précisément les jours ouvrables en excluant weekends et jours fériés.

**Méthodes principales :**

```php
// Calcule les jours ouvrables entre deux dates
public function calculateWorkingDays(Carbon $startDate, Carbon $endDate): int

// Vérifie si une date est un jour férié
public function isHoliday(Carbon $date): bool

// Obtient tous les jours fériés pour une année
public function getHolidaysForYear(int $year): Collection
```

#### `LeavePdfService`
**Fichier :** `app/Services/LeavePdfService.php`

Génère des résumés PDF des demandes de congés avec historique complet.

**Méthodes principales :**

```php
// Génère un PDF pour une demande de congé
public function generateLeaveSummary(LeaveApplication $leave): string

// Inclut l'historique d'approbation
private function buildApprovalHistory(LeaveApplication $leave): array
```

### Contrôleurs Modifiés

#### `LeaveBalanceController`
**Fichier :** `app/Http/Controllers/LeaveBalanceController.php`

**Nouvelles méthodes :**

```php
// Ajuste manuellement un solde de congés (RH uniquement)
public function adjust(Request $request, $leaveBalanceId)

// Récupère l'historique des mouvements pour un solde
public function getMovements($leaveBalanceId)

// Récupère les soldes d'un employé spécifique
public function getEmployeeBalances($employeeId)
```

#### `LeaveApplicationController`
**Fichier :** `app/Http/Controllers/LeaveApplicationController.php`

Intégration des services de calcul et d'approbation.

```php
// Utilise LeaveCalculationService pour calculer les jours
// Vérifie les soldes disponibles avant création
// Intègre le workflow d'approbation multi-niveaux
```

---

## 🎨 Phase 3 : Améliorations Frontend

### Composants React Créés/Modifiés

#### `ApprovalTracker.tsx`
**Fichier :** `resources/js/components/Leave/ApprovalTracker.tsx`

Affichage visuel du workflow d'approbation à 4 étapes.

**Fonctionnalités :**
- Barre de progression avec 4 étapes
- Indicateurs de statut (en attente, approuvé, rejeté)
- Affichage des approbateurs et dates
- Support du mode sombre

```tsx
<ApprovalTracker
  currentStage={leave.current_stage}
  status={leave.status}
  approvals={leave.approvals}
/>
```

#### `MovementLogsModal`
**Fichier :** `resources/js/pages/hr/leave-balances/index.tsx` (composant inline)

Modal affichant l'historique complet des ajustements de solde.

**Fonctionnalités :**
- Timeline chronologique des mouvements
- Codage couleur (vert = ajout, rouge = déduction)
- Badges de type d'ajustement
- Affichage des raisons et créateurs
- États de chargement et vides

```tsx
<MovementLogsModal
  isOpen={isMovementsModalOpen}
  onClose={() => setIsMovementsModalOpen(false)}
  movements={movements}
  isLoading={isLoadingMovements}
  balanceInfo="Employé - Type de congé (Année)"
/>
```

#### `LeaveBalancesTab`
**Fichier :** `resources/js/pages/hr/employees/show.tsx`

Onglet de visualisation des soldes de congés dans le profil employé.

**Fonctionnalités :**
- Cartes visuelles par type de congé
- Grille de statistiques (alloué, reporté, ajusté, utilisé)
- Barre de progression d'utilisation
- Affichage des raisons d'ajustement
- Support du mode sombre

```tsx
<LeaveBalancesTab employeeId={employee.id} />
```

### Pages Modifiées

#### `resources/js/pages/hr/leave-balances/index.tsx`
**Améliorations :**
- Ajout du bouton "Historique" pour chaque solde
- Intégration du `MovementLogsModal`
- Gestion d'état pour les mouvements
- Appels API pour récupérer l'historique

#### `resources/js/pages/hr/employees/show.tsx`
**Améliorations :**
- Ajout de l'onglet "Soldes de Congés"
- Affichage visuel des soldes avec progress bars
- Récupération asynchrone des données via Axios
- États de chargement et messages d'erreur

#### `resources/js/pages/hr/leave-applications/index.tsx`
**Améliorations :**
- Intégration du `ApprovalTracker` à 4 étapes
- Affichage de la progression d'approbation
- Boutons d'action conditionnels selon l'étape

### Système de Types TypeScript

#### `resources/js/types/crud.ts`
**Améliorations :**

```typescript
// Support pour les modals personnalisées
interface CrudFormModalProps {
  children?: React.ReactNode;
  contentOnly?: boolean;
  onSubmit?: (data: any) => void;
  formConfig?: {...};
}

// Support pour les callbacks avec données additionnelles
interface FormField {
  onDependentChange?: (
    fieldName: string,
    value: string,
    formData: Record<string, any>,
    additionalData?: any
  ) => void;
}
```

---

## 🛣️ Routes Ajoutées

### Backend (`routes/web.php`)

```php
// Paramètres de congés (Super Admin)
Route::get('hr/leave-settings', [LeaveSettingController::class, 'index'])
  ->name('hr.leave-settings.index');
Route::post('hr/leave-settings', [LeaveSettingController::class, 'update'])
  ->name('hr.leave-settings.store');

// Ajustements de soldes
Route::put('hr/leave-balances/{leaveBalance}/adjust', 
  [LeaveBalanceController::class, 'adjust'])
  ->name('hr.leave-balances.adjust');

// Historique des mouvements
Route::get('hr/leave-balances/{leaveBalance}/movements',
  [LeaveBalanceController::class, 'getMovements'])
  ->name('hr.leave-balances.movements');

// Soldes par employé
Route::get('hr/employees/{employee}/leave-balances',
  [LeaveBalanceController::class, 'getEmployeeBalances'])
  ->name('hr.employees.leave-balances');
```

---

## 🔐 Permissions Requises

### Nouvelles Permissions

```
- manage-leave-settings (Super Admin)
- adjust-leave-balances (RH)
- approve-leave-applications (Managers, RH, Directeur)
- reject-leave-applications (Managers, RH, Directeur)
- view-leave-balances (Tous)
```

---

## 📊 Flux de Données

### Création d'une Demande de Congé

```
1. Employé soumet une demande
   ↓
2. LeaveCalculationService calcule les jours ouvrables
   ↓
3. Vérification du solde disponible
   ↓
4. Création de la demande (status: pending, current_stage: 1)
   ↓
5. Notification au Manager 1
```

### Workflow d'Approbation

```
1. Manager 1 approuve (current_stage: 1 → 2)
   ↓
2. Manager 2 approuve OU étape sautée (current_stage: 2 → 3)
   ↓
3. RH approuve (current_stage: 3 → 4)
   ↓
4. Directeur approuve (status: approved)
   ↓
5. Déduction automatique du solde
   ↓
6. Création d'un mouvement de type 'used'
   ↓
7. Génération du PDF de résumé
```

### Ajustement Manuel de Solde

```
1. RH accède à la page des soldes
   ↓
2. Clique sur "Ajuster" pour un solde
   ↓
3. Saisit le montant et la raison
   ↓
4. LeaveBalanceController::adjust() traite la demande
   ↓
5. Création d'un mouvement de type 'adjusted'
   ↓
6. Recalcul du solde restant
   ↓
7. Mise à jour de l'interface
```

---

## 🧪 Tests

### Tests Unitaires

**Fichier :** `tests/Unit/LeaveApprovalServiceTest.php`

Tests du service d'approbation :
- Progression à travers les 4 étapes
- Saut de l'étape 2 si non configurée
- Vérification des permissions d'approbation
- Gestion des rejets

**Fichier :** `tests/Unit/LeaveCalculationServiceTest.php`

Tests du service de calcul :
- Calcul des jours ouvrables
- Exclusion des weekends
- Exclusion des jours fériés
- Cas limites (même jour, périodes courtes)

### Tests d'Intégration

À implémenter :
- Workflow complet de demande → approbation → déduction
- Ajustements manuels et historique
- Génération de PDF
- Calculs de report annuel

---

## 📁 Structure des Fichiers

```
app/
├── Http/Controllers/
│   ├── LeaveApplicationController.php (modifié)
│   ├── LeaveBalanceController.php (modifié)
│   └── LeaveSettingController.php (nouveau)
├── Services/
│   ├── LeaveApprovalService.php (nouveau)
│   ├── LeaveCalculationService.php (nouveau)
│   └── LeavePdfService.php (nouveau)
└── Models/
    ├── LeaveMovement.php (nouveau)
    └── LeaveSetting.php (nouveau)

database/migrations/
├── xxxx_create_leave_settings_table.php
├── xxxx_create_leave_movements_table.php
├── xxxx_add_validators_to_departments_table.php
└── xxxx_update_leave_balances_table.php

resources/js/
├── components/
│   └── Leave/
│       ├── ApprovalTracker.tsx (modifié)
│       └── ApprovalModal.tsx
├── pages/hr/
│   ├── leave-settings/index.tsx (nouveau)
│   ├── leave-balances/index.tsx (modifié)
│   ├── leave-applications/index.tsx (modifié)
│   └── employees/show.tsx (modifié)
└── types/
    └── crud.ts (modifié)

routes/
└── web.php (modifié)
```

---

## 🚀 Déploiement

### Étapes de Migration

```bash
# 1. Exécuter les migrations
php artisan migrate

# 2. Configurer les paramètres par défaut
php artisan db:seed --class=LeaveSettingsSeeder

# 3. Assigner les validateurs aux départements
# (Via l'interface admin ou manuellement en base)

# 4. Compiler les assets frontend
npm run build

# 5. Vider les caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

### Configuration Initiale

1. **Super Admin** : Configurer les paramètres globaux dans "Paramètres de Congés"
2. **RH** : Assigner les validateurs (Manager 1, Manager 2) à chaque département
3. **RH** : Initialiser les soldes de congés pour tous les employés
4. **Test** : Soumettre une demande de congé test et vérifier le workflow complet

---

## 🔍 Points d'Attention

### Performance

- Les calculs de jours ouvrables peuvent être coûteux pour de longues périodes
- Considérer la mise en cache des jours fériés par année
- Indexer `leave_balance_id` dans la table `leave_movements`

### Sécurité

- Toutes les routes sont protégées par permissions
- Validation stricte des montants d'ajustement
- Audit complet via `leave_movements`
- Vérification des soldes avant approbation finale

### Maintenance

- Nettoyer régulièrement les anciennes données de mouvements (>5 ans)
- Archiver les demandes de congés anciennes
- Monitorer les performances des calculs de jours

---

## 📞 Support & Contribution

### Conventions de Code

- **Backend** : PSR-12 pour PHP
- **Frontend** : ESLint + Prettier pour TypeScript/React
- **Base de données** : Migrations versionnées avec rollback

### Documentation API

Toutes les routes API retournent des réponses JSON standardisées :

```json
{
  "success": true,
  "data": {...},
  "message": "Operation completed successfully"
}
```

En cas d'erreur :

```json
{
  "success": false,
  "error": "Error message",
  "errors": {...}
}
```

---

## ✅ Checklist de Vérification

- [x] Migrations de base de données exécutées
- [x] Services backend implémentés et testés
- [x] Workflow d'approbation à 4 niveaux fonctionnel
- [x] Interface RH avec historique des mouvements
- [x] Interface employé avec visualisation des soldes
- [x] Calcul des jours ouvrables avec exclusion des fériés
- [x] Système de permissions configuré
- [ ] Tests unitaires complets (Phase 4)
- [ ] Tests d'intégration (Phase 4)
- [ ] Génération PDF validée (Phase 4)
- [ ] Documentation utilisateur finale