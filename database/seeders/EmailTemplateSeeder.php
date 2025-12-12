<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use App\Models\EmailTemplateLang;
use App\Models\UserEmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $languages = json_decode(file_get_contents(resource_path('lang/language.json')), true);
        $langCodes = collect($languages)->pluck('code')->toArray();
        
       $templates = [
            [
                'name' => 'Appointment Created',
                'from' => env('APP_NAME'),
                'translations' => [
                    'en' => [
                        'subject' => 'Appointment Confirmation',
                        'content' => '<p>Dear {appointment_name},</p><p>Your appointment has been successfully created.</p><p><strong>Details:</strong></p><ul><li>Email: {appointment_email}</li><li>Phone: {appointment_phone}</li><li>Date: {appointment_date}</li><li>Time: {appointment_time}</li></ul><p>Thank you for choosing {app_name}.</p>'
                    ],
                    'es' => [
                        'subject' => 'Confirmación de Cita',
                        'content' => '<p>Estimado/a {appointment_name},</p><p>Su cita ha sido creada exitosamente.</p><p><strong>Detalles:</strong></p><ul><li>Email: {appointment_email}</li><li>Teléfono: {appointment_phone}</li><li>Fecha: {appointment_date}</li><li>Hora: {appointment_time}</li></ul><p>Gracias por elegir {app_name}.</p>'
                    ],
                    'fr' => [
                        'subject' => 'Confirmation de rendez-vous',
                        'content' => '<p>Bonjour {appointment_name},</p><p>Votre rendez-vous a été créé avec succès.</p><p><strong>Détails :</strong></p><ul><li>Email : {appointment_email}</li><li>Téléphone : {appointment_phone}</li><li>Date : {appointment_date}</li><li>Heure : {appointment_time}</li></ul><p>Merci d’avoir choisi {app_name}.</p>'
                    ]
                ]
            ],
            [
                'name' => 'User Created',
                'from' => 'Support Team',
                'translations' => [
                    'en' => [
                        'subject' => 'Welcome to our platform - {user_name}',
                        'content' => '<p>Hello {user_name},</p><p>Your account has been successfully created.</p><p><strong>Login Details:</strong></p><ul><li>Website: {app_url}</li><li>Email: {user_email}</li><li>Password: {user_password}</li><li>Account Type: {user_type}</li></ul><p>Please keep this information secure.</p><p>Best regards,<br>Support Team</p>'
                    ],
                    'es' => [
                        'subject' => 'Bienvenido a nuestra plataforma - {user_name}',
                        'content' => '<p>Hola {user_name},</p><p>Su cuenta ha sido creada exitosamente.</p><p><strong>Detalles de acceso:</strong></p><ul><li>Sitio web: {app_url}</li><li>Email: {user_email}</li><li>Contraseña: {user_password}</li><li>Tipo de cuenta: {user_type}</li></ul><p>Por favor mantenga esta información segura.</p><p>Saludos cordiales,<br>Equipo de Soporte</p>'
                    ],
                    'fr' => [
                        'subject' => 'Bienvenue sur notre plateforme - {user_name}',
                        'content' => '<p>Bonjour {user_name},</p><p>Votre compte a été créé avec succès.</p><p><strong>Détails de connexion :</strong></p><ul><li>Site web : {app_url}</li><li>Email : {user_email}</li><li>Mot de passe : {user_password}</li><li>Type de compte : {user_type}</li></ul><p>Merci de conserver ces informations en lieu sûr.</p><p>Cordialement,<br>Équipe de support</p>'
                    ]
                ]
            ]
        ];


        foreach ($templates as $templateData) {
            $template = EmailTemplate::create([
                'name' => $templateData['name'],
                'from' => $templateData['from'],
                'user_id' => 1
            ]);

            foreach ($langCodes as $langCode) {
                $translation = $templateData['translations'][$langCode] ?? $templateData['translations']['en'];
                
                EmailTemplateLang::create([
                    'parent_id' => $template->id,
                    'lang' => $langCode,
                    'subject' => $translation['subject'],
                    'content' => $translation['content']
                ]);
            }

            UserEmailTemplate::create([
                'template_id' => $template->id,
                'user_id' => 1,
                'is_active' => true
            ]);
        }
    }
}
