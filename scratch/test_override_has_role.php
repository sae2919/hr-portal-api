<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    Illuminate\Http\Request::capture()
);

use App\Models\User;
use Spatie\Permission\Traits\HasRoles;

class UserWithOverride extends User {
    use HasRoles {
        hasRole as spatieHasRole;
    }

    protected $table = 'users';

    public function hasRole($roles, string $guard = null): bool
    {
        if (is_string($roles)) {
            $roles = [$roles];
        } elseif ($roles instanceof \Illuminate\Support\Collection) {
            $roles = $roles->all();
        } else {
            $roles = (array) $roles;
        }

        foreach ($roles as $role) {
            if ($this->spatieHasRole($role, $guard)) {
                return true;
            }
        }

        $roleCol = strtolower(str_replace([' ', '-'], '_', $this->role ?? ''));
        foreach ($roles as $role) {
            $normalizedRole = strtolower(str_replace([' ', '-'], '_', $role));
            if ($roleCol === $normalizedRole) {
                return true;
            }
            if (($normalizedRole === 'admin' || $normalizedRole === 'super_admin') && 
                ($roleCol === 'admin' || $roleCol === 'super_admin' || $roleCol === 'superadmin')) {
                return true;
            }
        }

        $designation = strtolower($this->employee?->designation?->title ?? '');
        if (!empty($designation)) {
            $resolvedTier = null;

            if (str_contains($designation, 'ceo') || 
                str_contains($designation, 'founder') || 
                str_contains($designation, 'president') || 
                str_contains($designation, 'co-founder') || 
                str_contains($designation, 'co_founder')) {
                $resolvedTier = 'admin';
            } 
            elseif (str_contains($designation, 'manager') || 
                    str_contains($designation, 'senior manager') || 
                    str_contains($designation, 'department manager')) {
                $resolvedTier = 'manager';
            } 
            else {
                $teamLeadKeywords = [
                    'sales head', 'seo lead', 'tech lead', 'technical lead',
                    'team lead', 'lead', 'head', 'seo head', 'dev lead'
                ];
                foreach ($teamLeadKeywords as $kw) {
                    if (str_contains($designation, $kw)) {
                        $resolvedTier = 'team_lead';
                        break;
                    }
                }
            }

            if ($resolvedTier) {
                foreach ($roles as $role) {
                    $normalizedRole = strtolower(str_replace([' ', '-'], '_', $role));
                    if ($resolvedTier === $normalizedRole) {
                        return true;
                    }
                    if ($resolvedTier === 'admin' && ($normalizedRole === 'admin' || $normalizedRole === 'super_admin')) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}

// Test users
$testCases = [
    'admin@hrportal.com' => ['admin', 'super_admin'],
    'hr@hrportal.com' => ['hr'],
    'vasa.raviteja@gmail.com' => ['team_lead'],
    'santoshasole9@gmail.com' => ['team_lead'], // Tech Lead (role is employee)
    'sshakthi507@gmail.com' => ['team_lead'], // Sales head (role is employee)
    'ladhwenavaneetha@gmail.com' => ['team_lead'], // content creater lead (role is employee)
    'srinivasbalam2003@gmail.com' => ['employee'], // Software Engineer
];

echo str_pad("Email", 30) . str_pad("Expected Role", 15) . "hasRole Test Result\n";
echo str_repeat("-", 65) . "\n";

foreach ($testCases as $email => $rolesToCheck) {
    $user = UserWithOverride::where('email', $email)->first();
    if ($user) {
        $designation = $user->employee?->designation?->title ?? 'None';
        echo str_pad($email, 30);
        
        $results = [];
        foreach ($rolesToCheck as $role) {
            $has = $user->hasRole($role) ? "TRUE" : "FALSE";
            $results[] = "$role: $has";
        }
        echo str_pad(implode(', ', $rolesToCheck), 20) . " | " . implode(', ', $results) . " (Desig: $designation)\n";
    } else {
        echo str_pad($email, 30) . "Not Found\n";
    }
}
