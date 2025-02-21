<?php

namespace Database\Seeders;

use App\Models\Feature;
use App\Models\FeatureCategory;
use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    public function run()
    {
        $packages = [
            [
                'type' => 'Basic',
                'price' => '0/month',
                'subscribed' => false,
                'categories' => [
                    [
                        'name' => 'Budgeting & Expense Tracking',
                        'features' => [
                            ['name' => 'Expense Tracking', 'is_available' => true, 'description' => ''],
                            ['name' => 'Basic Budgeting', 'is_available' => true, 'description' => ''],
                            ['name' => 'Advanced Budgeting', 'is_available' => false, 'description' => 'Custom budget categories and limits', 'not_available_reason' => 'Upgrade to Grow or Master'],
                            ['name' => 'Financial Snapshot', 'is_available' => true, 'description' => 'Quick overview of income vs expenses'],
                            ['name' => 'Bill Reminders', 'is_available' => true, 'description' => ''],
                        ],
                    ],
                    [
                        'name' => 'Savings & Goals',
                        'features' => [
                            ['name' => 'Savings Goal Starter', 'is_available' => true, 'description' => 'Set one savings target'],
                            ['name' => 'Goal Planning (Multiple)', 'is_available' => false, 'description' => 'Track multiple savings goals', 'not_available_reason' => 'Upgrade to Grow or Master'],
                            ['name' => 'Debt Management', 'is_available' => false, 'description' => 'Strategies to pay off debts', 'not_available_reason' => 'Upgrade to Grow or Master'],
                        ],
                    ],
                    [
                        'name' => 'Investment & Portfolio Management',
                        'features' => [
                            ['name' => 'Investment Tracking', 'is_available' => false, 'description' => 'Monitor investment performance', 'not_available_reason' => 'Upgrade to Grow or Master'],
                            ['name' => 'Portfolio Management', 'is_available' => false, 'description' => 'Manage multiple investment accounts', 'not_available_reason' => 'Upgrade to Master'],
                            ['name' => 'Cash Flow Analysis', 'is_available' => false, 'description' => 'Track money in and out over time', 'not_available_reason' => 'Upgrade to Grow or Master'],
                            ['name' => 'Tax Estimation', 'is_available' => false, 'description' => 'Estimate tax obligations', 'not_available_reason' => 'Upgrade to Master'],
                            ['name' => 'Tax Optimization', 'is_available' => false, 'description' => 'Strategies to reduce tax liability', 'not_available_reason' => 'Upgrade to Master'],
                        ],
                    ],
                    [
                        'name' => 'Advanced Financial Analysis',
                        'features' => [
                            ['name' => 'Financial Analysis', 'is_available' => false, 'description' => 'Detailed breakdown of financial health', 'not_available_reason' => 'Upgrade to Grow or Master'],
                            ['name' => 'Predictive Analytics', 'is_available' => false, 'description' => 'Forecast future financial trends', 'not_available_reason' => 'Upgrade to Master'],
                            ['name' => 'Real-Time Financial Advice', 'is_available' => false, 'description' => 'Instant AI-driven recommendations', 'not_available_reason' => 'Upgrade to Master'],
                            ['name' => 'Custom Reports', 'is_available' => false, 'description' => 'Tailored financial summaries', 'not_available_reason' => 'Upgrade to Master'],
                        ],
                    ],
                    [
                        'name' => 'Support & Customization',
                        'features' => [
                            ['name' => 'AI Chat Assistant', 'is_available' => true, 'description' => ''],
                            ['name' => 'AI Chat Assistant (Enhanced)', 'is_available' => false, 'description' => 'More detailed AI responses', 'not_available_reason' => 'Upgrade to Grow or Master'],
                            ['name' => 'AI Virtual Accountant (Full)', 'is_available' => false, 'description' => 'Comprehensive AI accounting support', 'not_available_reason' => 'Upgrade to Master'],
                            ['name' => 'Priority Support', 'is_available' => false, 'description' => 'Faster customer service responses', 'not_available_reason' => 'Upgrade to Grow or Master'],
                            ['name' => 'Personalized Reports', 'is_available' => false, 'description' => 'Reports customized to your needs', 'not_available_reason' => 'Upgrade to Master'],
                            ['name' => 'Early Access to New Features', 'is_available' => false, 'description' => '', 'not_available_reason' => 'Upgrade to Grow or Master'],
                            ['name' => 'Unlock Deep Integration', 'is_available' => true, 'description' => 'Connect with external financial apps'],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'Grow',
                'price' => '400/month',
                'subscribed' => false,
                'categories' => [
                    [
                        'name' => 'Budgeting & Expense Tracking',
                        'features' => [
                            ['name' => 'Expense Tracking', 'is_available' => true, 'description' => ''],
                            ['name' => 'Basic Budgeting', 'is_available' => true, 'description' => ''],
                            ['name' => 'Advanced Budgeting', 'is_available' => true, 'description' => 'Custom budget categories and limits'],
                            ['name' => 'Financial Snapshot', 'is_available' => true, 'description' => 'Quick overview of income vs expenses'],
                            ['name' => 'Bill Reminders', 'is_available' => true, 'description' => ''],
                        ],
                    ],
                    [
                        'name' => 'Savings & Goals',
                        'features' => [
                            ['name' => 'Savings Goal Starter', 'is_available' => true, 'description' => 'Set one savings target'],
                            ['name' => 'Goal Planning (Multiple)', 'is_available' => true, 'description' => 'Track multiple savings goals'],
                            ['name' => 'Debt Management', 'is_available' => true, 'description' => 'Strategies to pay off debts'],
                        ],
                    ],
                    [
                        'name' => 'Investment & Portfolio Management',
                        'features' => [
                            ['name' => 'Investment Tracking', 'is_available' => true, 'description' => 'Monitor investment performance'],
                            ['name' => 'Portfolio Management', 'is_available' => false, 'description' => 'Manage multiple investment accounts', 'not_available_reason' => 'Upgrade to Master'],
                            ['name' => 'Cash Flow Analysis', 'is_available' => true, 'description' => 'Track money in and out over time'],
                            ['name' => 'Tax Estimation', 'is_available' => false, 'description' => 'Estimate tax obligations', 'not_available_reason' => 'Upgrade to Master'],
                            ['name' => 'Tax Optimization', 'is_available' => false, 'description' => 'Strategies to reduce tax liability', 'not_available_reason' => 'Upgrade to Master'],
                        ],
                    ],
                    [
                        'name' => 'Advanced Financial Analysis',
                        'features' => [
                            ['name' => 'Financial Analysis', 'is_available' => true, 'description' => 'Detailed breakdown of financial health'],
                            ['name' => 'Predictive Analytics', 'is_available' => false, 'description' => 'Forecast future financial trends', 'not_available_reason' => 'Upgrade to Master'],
                            ['name' => 'Real-Time Financial Advice', 'is_available' => false, 'description' => 'Instant AI-driven recommendations', 'not_available_reason' => 'Upgrade to Master'],
                            ['name' => 'Custom Reports', 'is_available' => false, 'description' => 'Tailored financial summaries', 'not_available_reason' => 'Upgrade to Master'],
                        ],
                    ],
                    [
                        'name' => 'Support & Customization',
                        'features' => [
                            ['name' => 'AI Chat Assistant', 'is_available' => true, 'description' => ''],
                            ['name' => 'AI Chat Assistant (Enhanced)', 'is_available' => true, 'description' => 'More detailed AI responses'],
                            ['name' => 'AI Virtual Accountant (Full)', 'is_available' => false, 'description' => 'Comprehensive AI accounting support', 'not_available_reason' => 'Upgrade to Master'],
                            ['name' => 'Priority Support', 'is_available' => true, 'description' => 'Faster customer service responses'],
                            ['name' => 'Personalized Reports', 'is_available' => false, 'description' => 'Reports customized to your needs', 'not_available_reason' => 'Upgrade to Master'],
                            ['name' => 'Early Access to New Features', 'is_available' => true, 'description' => ''],
                            ['name' => 'Unlock Deep Integration', 'is_available' => true, 'description' => 'Connect with external financial apps'],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'Master',
                'price' => '1000/month',
                'subscribed' => false,
                'categories' => [
                    [
                        'name' => 'Budgeting & Expense Tracking',
                        'features' => [
                            ['name' => 'Expense Tracking', 'is_available' => true, 'description' => ''],
                            ['name' => 'Basic Budgeting', 'is_available' => true, 'description' => ''],
                            ['name' => 'Advanced Budgeting', 'is_available' => true, 'description' => 'Custom budget categories and limits'],
                            ['name' => 'Financial Snapshot', 'is_available' => true, 'description' => 'Quick overview of income vs expenses'],
                            ['name' => 'Bill Reminders', 'is_available' => true, 'description' => ''],
                        ],
                    ],
                    [
                        'name' => 'Savings & Goals',
                        'features' => [
                            ['name' => 'Savings Goal Starter', 'is_available' => true, 'description' => 'Set one savings target'],
                            ['name' => 'Goal Planning (Multiple)', 'is_available' => true, 'description' => 'Track multiple savings goals'],
                            ['name' => 'Debt Management', 'is_available' => true, 'description' => 'Strategies to pay off debts'],
                        ],
                    ],
                    [
                        'name' => 'Investment & Portfolio Management',
                        'features' => [
                            ['name' => 'Investment Tracking', 'is_available' => true, 'description' => 'Monitor investment performance'],
                            ['name' => 'Portfolio Management', 'is_available' => true, 'description' => 'Manage multiple investment accounts'],
                            ['name' => 'Cash Flow Analysis', 'is_available' => true, 'description' => 'Track money in and out over time'],
                            ['name' => 'Tax Estimation', 'is_available' => true, 'description' => 'Estimate tax obligations'],
                            ['name' => 'Tax Optimization', 'is_available' => true, 'description' => 'Strategies to reduce tax liability'],
                        ],
                    ],
                    [
                        'name' => 'Advanced Financial Analysis',
                        'features' => [
                            ['name' => 'Financial Analysis', 'is_available' => true, 'description' => 'Detailed breakdown of financial health'],
                            ['name' => 'Predictive Analytics', 'is_available' => true, 'description' => 'Forecast future financial trends'],
                            ['name' => 'Real-Time Financial Advice', 'is_available' => true, 'description' => 'Instant AI-driven recommendations'],
                            ['name' => 'Custom Reports', 'is_available' => true, 'description' => 'Tailored financial summaries'],
                        ],
                    ],
                    [
                        'name' => 'Support & Customization',
                        'features' => [
                            ['name' => 'AI Chat Assistant', 'is_available' => true, 'description' => ''],
                            ['name' => 'AI Chat Assistant (Enhanced)', 'is_available' => true, 'description' => 'More detailed AI responses'],
                            ['name' => 'AI Virtual Accountant (Full)', 'is_available' => true, 'description' => 'Comprehensive AI accounting support'],
                            ['name' => 'Priority Support', 'is_available' => true, 'description' => 'Faster customer service responses'],
                            ['name' => 'Personalized Reports', 'is_available' => true, 'description' => 'Reports customized to your needs'],
                            ['name' => 'Early Access to New Features', 'is_available' => true, 'description' => ''],
                            ['name' => 'Unlock Deep Integration', 'is_available' => true, 'description' => 'Connect with external financial apps'],
                        ],
                    ],
                ],
            ],
        ];

        foreach ($packages as $packageData) {
            $package = Package::create([
                'type' => $packageData['type'],
                'price' => $packageData['price'],
                'subscribed' => $packageData['subscribed'],
            ]);

            foreach ($packageData['categories'] as $categoryData) {
                $category = FeatureCategory::create([
                    'package_id' => $package->id,
                    'name' => $categoryData['name'],
                ]);

                foreach ($categoryData['features'] as $featureData) {
                    Feature::create([
                        'feature_category_id' => $category->id,
                        'name' => $featureData['name'],
                        'is_available' => $featureData['is_available'],
                        'description' => $featureData['description'],
                        'not_available_reason' => $featureData['not_available_reason'] ?? null,
                    ]);
                }
            }
        }
    }
}