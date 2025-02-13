<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Common\Enums\VerificationType;
use App\Models\DocumentType;

class DocumentTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Loop through each enum case
        foreach (VerificationType::cases() as $case) {
            DocumentType::firstOrCreate([
                'name' => $case->value,
                'input_type' => $this->getInputType($case),
                'is_required' => $this->isRequired($case),
                'country_code' => "NG",
                
            ]);
        }
    }

    /**
     * Determine the input type based on the verification type.
     */
    private function getInputType(VerificationType $type): string
    {
        return match ($type) {
            VerificationType::FACIAL_RECOGNITION,
            VerificationType::FINGERPRINT_RECOGNITION,
            VerificationType::IRIS_RECOGNITION,
            VerificationType::PASSPORT,
            VerificationType::DRIVER_LICENSE,
            VerificationType::VOTER_CARD,
            VerificationType::NATIONAL_ID_CARD,
            VerificationType::RESIDENCE_PERMIT,
            VerificationType::INTERNATIONAL_PASSPORT,
            VerificationType::FOREIGN_NATIONAL_ID_CARD,
            VerificationType::PERMANENT_RESIDENT_CARD,
            VerificationType::WORK_PERMIT,
            VerificationType::STUDENT_ID_CARD,
            VerificationType::REFUGEE_ID_CARD,
            VerificationType::ID_CARD,
            VerificationType::STATE_ID_CARD,
            VerificationType::CITY_ID_CARD,
            VerificationType::HEALTH_INSURANCE_CARD,
            VerificationType::NATIONAL_HEALTH_INSURANCE_CARD,
            VerificationType::SOCIAL_SECURITY_CARD,
            VerificationType::NATIONAL_SOCIAL_SECURITY_CARD => 'image',

            default => 'text',
        };
    }

    /**
     * Determine if the document is required by default.
     */
    private function isRequired(VerificationType $type): bool
    {
        return match ($type) {
            VerificationType::NIN,
            VerificationType::BVN,
            VerificationType::PASSPORT,
            VerificationType::DRIVER_LICENSE,
            VerificationType::NATIONAL_ID_CARD,
            VerificationType::TAX_IDENTIFICATION_NUMBER,
            VerificationType::INTERNATIONAL_PASSPORT,
            VerificationType::SOCIAL_SECURITY_CARD,
            VerificationType::NATIONAL_SOCIAL_SECURITY_CARD => true,

            default => false,
        };
    }


}