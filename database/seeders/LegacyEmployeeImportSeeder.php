<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Designation;
use App\Models\ContractType;
use Carbon\Carbon;

class LegacyEmployeeImportSeeder extends Seeder
{
    private int $defaultBranchId = 1;
    private int $defaultCreatedBy = 4;
    
    // Cache pour éviter les requêtes répétées
    private array $departments = [];
    private array $designations = [];
    private array $contractTypes = [];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::beginTransaction();
        
        try {
            // Charger les données legacy depuis la table temporaire
            $legacyEmployees = $this->getLegacyData();
            
            $this->command->info('🚀 Starting import of ' . count($legacyEmployees) . ' employees...');
            $this->command->newLine();
            
            // Phase 1: Créer les départements (SERVICE unique)
            $this->command->info('📁 Phase 1: Creating departments from SERVICE...');
            $this->createDepartments($legacyEmployees);
            
            // Phase 2: Créer les désignations (POSTE unique par département)
            $this->command->info('📋 Phase 2: Creating designations from POSTE...');
            $this->createDesignations($legacyEmployees);
            
            // Phase 3: Créer les types de contrat (STATUT unique)
            $this->command->info('📄 Phase 3: Creating contract types from STATUT...');
            $this->createContractTypes($legacyEmployees);
            
            // Phase 4: Importer les employés
            $this->command->info('👥 Phase 4: Importing employees...');
            $this->importEmployees($legacyEmployees);
            
            DB::commit();
            $this->command->newLine();
            $this->command->info('✅ Import completed successfully!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->newLine();
            $this->command->error('❌ Import failed - All changes rolled back!');
            $this->command->error('Error: ' . $e->getMessage());
            $this->command->error('File: ' . $e->getFile() . ':' . $e->getLine());
            throw $e;
        }
    }

    /**
     * Charger les données depuis la table temporaire 'employes'
     * Assurez-vous d'avoir importé le fichier SQL avant d'exécuter ce seeder
     */
    private function getLegacyData(): array
    {
        // Vérifier si la table existe
        if (!Schema::hasTable('employes')) {
            throw new \Exception(
                "La table 'employes' n'existe pas. " .
                "Veuillez d'abord importer le fichier Employes_Info_Generale.sql dans votre base de données."
            );
        }
        
        $data = DB::table('employes')->get()->toArray();
        
        if (empty($data)) {
            throw new \Exception("La table 'employes' est vide. Aucune donnée à importer.");
        }
        
        // Convertir en array associatif
        return array_map(function ($row) {
            return (array) $row;
        }, $data);
    }

    /**
     * Créer les départements à partir des SERVICE uniques
     */
    private function createDepartments(array $employees): void
    {
        $services = collect($employees)
            ->pluck('SERVICE')
            ->unique()
            ->filter(fn($s) => !empty(trim($s ?? '')))
            ->values();
        
        $created = 0;
        $existing = 0;
        
        foreach ($services as $service) {
            $serviceName = trim($service);
            
            $dept = Department::firstOrCreate(
                [
                    'name' => $serviceName,
                    'branch_id' => $this->defaultBranchId
                ],
                [
                    'description' => "Imported from legacy system - SERVICE: {$serviceName}",
                    'status' => 'active',
                    'created_by' => $this->defaultCreatedBy,
                ]
            );
            
            if ($dept->wasRecentlyCreated) {
                $created++;
            } else {
                $existing++;
            }
            
            $this->departments[$serviceName] = $dept->id;
        }
        
        $this->command->info("   → Created: {$created} | Already existed: {$existing} | Total: " . count($this->departments));
    }

    /**
     * Créer les désignations à partir des POSTE uniques par département
     */
    private function createDesignations(array $employees): void
    {
        // Grouper par SERVICE + POSTE pour éviter les doublons
        $postes = collect($employees)
            ->filter(fn($e) => !empty(trim($e['POSTE'] ?? '')) && !empty(trim($e['SERVICE'] ?? '')))
            ->unique(fn($e) => trim($e['SERVICE']) . '|' . trim($e['POSTE']));
        
        $created = 0;
        $existing = 0;
        $skipped = 0;
        
        foreach ($postes as $employee) {
            $serviceName = trim($employee['SERVICE']);
            $posteName = trim($employee['POSTE']);
            
            $departmentId = $this->departments[$serviceName] ?? null;
            
            if (!$departmentId) {
                $skipped++;
                continue;
            }
            
            $designation = Designation::firstOrCreate(
                [
                    'name' => $posteName,
                    'department_id' => $departmentId
                ],
                [
                    'description' => "Imported from legacy system - POSTE: {$posteName}",
                    'status' => 'active',
                    'created_by' => $this->defaultCreatedBy,
                ]
            );
            
            if ($designation->wasRecentlyCreated) {
                $created++;
            } else {
                $existing++;
            }
            
            $key = $serviceName . '|' . $posteName;
            $this->designations[$key] = $designation->id;
        }
        
        $this->command->info("   → Created: {$created} | Already existed: {$existing} | Skipped: {$skipped} | Total: " . count($this->designations));
    }

    /**
     * Créer les types de contrat à partir des STATUT uniques
     */
    private function createContractTypes(array $employees): void
    {
        $statuts = collect($employees)
            ->pluck('STATUT')
            ->unique()
            ->filter(fn($s) => !empty(trim($s ?? '')))
            ->values();
        
        $created = 0;
        $existing = 0;
        
        foreach ($statuts as $statut) {
            $statutName = trim($statut);
            
            $contractType = ContractType::firstOrCreate(
                [
                    'name' => $statutName,
                    'created_by' => $this->defaultCreatedBy
                ],
                [
                    'description' => "Imported from legacy system - STATUT: {$statutName}",
                    'default_duration_months' => null,
                    'probation_period_months' => 3,
                    'notice_period_days' => 30,
                    'is_renewable' => true,
                    'status' => 'active',
                ]
            );
            
            if ($contractType->wasRecentlyCreated) {
                $created++;
            } else {
                $existing++;
            }
            
            $this->contractTypes[$statutName] = $contractType->id;
        }
        
        $this->command->info("   → Created: {$created} | Already existed: {$existing} | Total: " . count($this->contractTypes));
    }

    /**
     * Importer les employés avec leurs relations
     */
    private function importEmployees(array $employees): void
    {
        $counter = 0;
        $errors = 0;
        $total = count($employees);
        
        foreach ($employees as $index => $emp) {
            try {
                // 1. Construire le nom complet
                $prenom = trim($emp['PRENOM'] ?? '');
                $nom = trim($emp['NOM'] ?? '');
                $fullName = trim("{$prenom} {$nom}");
                
                if (empty($fullName)) {
                    $fullName = 'Employee ' . ($index + 1);
                }
                
                // 2. Générer un email unique
                $email = 'employee' . ($index + 1) . '@email.com';
                
                // Vérifier si l'email existe déjà
                $existingUser = User::where('email', $email)->first();
                if ($existingUser) {
                    $email = 'employee' . ($index + 1) . '_' . time() . '@email.com';
                }
                
                // 3. Créer le User
                $user = User::create([
                    'name' => $fullName,
                    'email' => $email,
                    'password' => Hash::make('password'),
                    'type' => 'employee',
                    'status' => 'active',
                    'created_by' => $this->defaultCreatedBy,
                ]);
                
                // 4. Préparer les références
                $serviceName = trim($emp['SERVICE'] ?? '');
                $posteName = trim($emp['POSTE'] ?? '');
                $statutName = trim($emp['STATUT'] ?? '');
                
                $departmentId = !empty($serviceName) ? ($this->departments[$serviceName] ?? null) : null;
                $designationKey = $serviceName . '|' . $posteName;
                $designationId = !empty($posteName) ? ($this->designations[$designationKey] ?? null) : null;
                $contractTypeId = !empty($statutName) ? ($this->contractTypes[$statutName] ?? null) : null;
                
                // 5. Générer l'employee_id
                $employeeId = trim($emp['MATRICULE_SOLDE'] ?? '');
                if (empty($employeeId)) {
                    $employeeId = 'EMP-' . str_pad($index + 1, 5, '0', STR_PAD_LEFT);
                }
                
                // Vérifier l'unicité de l'employee_id
                $existingEmployee = Employee::where('employee_id', $employeeId)->first();
                if ($existingEmployee) {
                    $employeeId = $employeeId . '-' . ($index + 1);
                }
                
                // 6. Créer l'Employee
                Employee::create([
                    // Basic Information
                    'employee_id' => $employeeId,
                    'phone' => $this->formatPhone($emp['CONTACT'] ?? null),
                    'date_of_birth' => $this->parseDate($emp['DATE_DE_NAISSANCE'] ?? null),
                    'gender' => $this->parseGender($emp['SEXE'] ?? null),
                    
                    // Employment Details
                    'branch_id' => $this->defaultBranchId,
                    'department_id' => $departmentId,
                    'designation_id' => $designationId,
                    'date_of_joining' => $this->parseDate($emp['DATE_DE_PRISE_DE_SERVICE'] ?? null),
                    'employment_type' => 'Full-time',
                    'contract_type_id' => $contractTypeId,
                    
                    // Legacy fields (nouveaux champs)
                    'matrimonial_status' => $emp['SITUATIONMATRI'] ?? null,
                    'cni_number' => $this->cleanCniNumber($emp['NUMEROCIN'] ?? null),
                    'registration_date' => $this->parseDate($emp['DATE_D_ENREGISTREMENT'] ?? null),
                    'role' => $posteName ?: null,
                    'personnel_type' => $emp['PERSONNEL'] ?? null,
                    'age_legacy' => $emp['AGE'] ?? null,
                    
                    // System fields
                    'user_id' => $user->id,
                    'created_by' => $this->defaultCreatedBy,
                ]);
                
                $counter++;
                
                // Progress indicator every 100 records
                if ($counter % 100 === 0) {
                    $percentage = round(($counter / $total) * 100);
                    $this->command->info("   → Progress: {$counter}/{$total} ({$percentage}%)");
                }
                
            } catch (\Exception $e) {
                $errors++;
                $this->command->warn("   ⚠ Error at row {$index} (MATRICULE: " . ($emp['MATRICULE_SOLDE'] ?? 'N/A') . "): " . $e->getMessage());
                
                // Si trop d'erreurs, arrêter
                if ($errors > 50) {
                    throw new \Exception("Too many errors ({$errors}). Stopping import.");
                }
                
                continue;
            }
        }
        
        $this->command->info("   → Imported: {$counter} employees | Errors: {$errors}");
    }

    /**
     * Formatter le numéro de téléphone
     */
    private function formatPhone($contact): ?string
    {
        if (empty($contact)) {
            return null;
        }
        
        // Convertir en string et nettoyer
        $phone = (string) $contact;
        
        // Supprimer le .0 à la fin (format float)
        $phone = preg_replace('/\.0$/', '', $phone);
        
        // Supprimer les espaces
        $phone = preg_replace('/\s+/', '', $phone);
        
        // Supprimer les caractères non numériques sauf +
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        return !empty($phone) ? $phone : null;
    }

    /**
     * Parser le genre
     */
    private function parseGender(?string $sexe): ?string
    {
        if (empty($sexe)) {
            return null;
        }
        
        $sexeUpper = strtoupper(trim($sexe));
        
        if (in_array($sexeUpper, ['MASCULIN', 'M', 'HOMME', 'MALE'])) {
            return 'male';
        }
        
        if (in_array($sexeUpper, ['FEMININ', 'FÉMININ', 'F', 'FEMME', 'FEMALE'])) {
            return 'female';
        }
        
        return 'other';
    }

    /**
     * Parser une date string vers format Y-m-d
     */
    private function parseDate(?string $date): ?string
    {
        if (empty($date)) {
            return null;
        }
        
        $date = trim($date);
        
        try {
            // Essayer plusieurs formats
            $formats = [
                'Y-m-d H:i:s',
                'Y-m-d',
                'd/m/Y',
                'd-m-Y',
                'Y/m/d',
            ];
            
            foreach ($formats as $format) {
                $parsed = \DateTime::createFromFormat($format, $date);
                if ($parsed !== false) {
                    return $parsed->format('Y-m-d');
                }
            }
            
            // Essayer avec Carbon en dernier recours
            return Carbon::parse($date)->format('Y-m-d');
            
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Nettoyer le numéro CNI (supprimer les URLs et caractères parasites)
     */
    private function cleanCniNumber(?string $cni): ?string
    {
        if (empty($cni)) {
            return null;
        }
        
        // Prendre uniquement la partie avant le premier #
        $parts = explode('#', $cni);
        $cleaned = trim($parts[0]);
        
        return !empty($cleaned) ? $cleaned : null;
    }
}
