<?php

namespace App\Modules\WhaleGPTModule\Services;

use App\Helpers\ResponseHelper;

class HomeCardService
{

 public function getTips()
 {

  $items = [
   [
    "priority_settings" => [
     "priority" => "High",
    ],
    "navigation" => [
     "callback_slug" => "GO_TO_PROFILE_DETAILS_VIEW",
    ],
    "display" => [
     "title" => "Complete Profile",
     "description" => "Finish setting up your profile to unlock all features.",
     "tile_color" => "#1C274C",
     "content_color" => "#FFFFFF",
     "text_colors" => ["BLUE", "PURPLE"],
    ],
    "content_data" => [
     "content" => "Please provide your details.",
     "context" => "profile_completion",
    ],
   ],
   [
    "priority_settings" => [
     "priority" => "Medium",
    ],
    "navigation" => [
     "callback_slug" => "GO_TO_PAYMENT_SETTINGS",
    ],
    "display" => [
     "title" => "Add Payment Method",
     "description" => "Link a card or bank account for seamless transactions.",
     "tile_color" => "#bbbccb", // Green
     "content_color" => "#1C274C", // Black
     "text_colors" => ["RED", "YELLOW"],
    ],
    "content_data" => [
     "content" => "Securely add your payment details.",
     "context" => "payment_setup",
    ],
   ],
   [
    "priority_settings" => [
     "priority" => "Low",
    ],
    "navigation" => [
     "callback_slug" => "GO_TO_NOTIFICATION_PREFERENCES",
    ],
    "display" => [
     "title" => "Set Notifications",
     "description" => "Choose how you want to receive updates.",
     "tile_color" => "#333333", // Blue
     "content_color" => "#FFFFFF", // White
     "text_colors" => ["GREEN", "ORANGE"],
    ],
    "content_data" => [
     "content" => " Customize your notification settings.",
     "context" => "notification_config",
    ],
   ],
   [
    "priority_settings" => [
     "priority" => "High",
    ],
    "navigation" => [
     "callback_slug" => "GO_TO_SECURITY_CHECK",
    ],
    "display" => [
     "title" => "Verify Identity",
     "description" => "Complete a quick security check to protect your account.",
     "tile_color" => "#414A61", // Pink
     "content_color" => "#FFFFFF", // Black
     "text_colors" => ["CYAN", "MAGENTA"],
    ],
    "content_data" => [
     "content" => "Ensure your account’s safety.",
     "context" => "security_verification",
    ],
   ],
  ];

  return ResponseHelper::success($items);

 }
}