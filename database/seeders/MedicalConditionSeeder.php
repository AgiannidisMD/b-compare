<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\MedicalCondition;
use Illuminate\Support\Str;

class MedicalConditionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $conditions = [
            'Diabetes' => 'Type 1, Type 2, and Prediabetes management',
            'Hypertension' => 'High blood pressure management',
            'Cardiovascular Disease' => 'Heart health and circulation support',
            'Arthritis' => 'Joint pain and arthritis management',
            'Osteoporosis' => 'Bone health and density support',
            'Digestive Issues' => 'IBS, constipation, bloating relief',
            'Immune Support' => 'Immune system strengthening',
            'Anxiety & Stress' => 'Mental health and stress management',
            'High Cholesterol' => 'Cholesterol level management',
            'Inflammation' => 'Chronic inflammation reduction',
            'Anemia' => 'Iron deficiency and anemia support',
            'Thyroid Issues' => 'Thyroid function support',
            'PCOS' => 'Polycystic Ovary Syndrome management',
            'Menopause' => 'Menopause symptom relief',
            'Pregnancy & Lactation' => 'Prenatal and lactation support',
            'Athletic Performance' => 'Energy, recovery, and performance',
            'Cognitive Function' => 'Memory, focus, and brain health',
            'Sleep Disorders' => 'Insomnia and sleep quality improvement',
        ];

        foreach ($conditions as $name => $description) {
            MedicalCondition::create([
                'name' => $name,
                'slug' => Str::slug($name),
                'description' => $description,
                'requires_doctor_approval' => false,
            ]);
        }
    }
}
