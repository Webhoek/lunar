<?php

namespace Lunar\Admin\Database\State;

use Illuminate\Support\Facades\Schema;
use Lunar\Admin\Support\Facades\LunarAccessControl;
use Lunar\Admin\Support\Facades\LunarPanel;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class EnsureBaseRolesAndPermissions
{
    public function prepare()
    {
        //
    }

    public function run()
    {
        $guard = LunarPanel::getPanel()->getAuthGuard();

        $tableNames = config('permission.table_names');

        if (Schema::hasTable($tableNames['roles'])) {
            foreach (LunarAccessControl::getBaseRoles() as $role) {
                Role::query()->firstOrCreate([
                    'name' => $role,
                    'guard_name' => $guard,
                ]);
            }
        }

        if (Schema::hasTable($tableNames['permissions'])) {
            // Rename any existing permissions
            Permission::where('name', 'tenancy:catalogue:manage-products')->update(['name' => 'tenancy:catalog:manage-products']);
            Permission::where('name', 'tenancy:catalogue:manage-collections')->update(['name' => 'tenancy:catalog:manage-collections']);
            Permission::where('name', 'tenancy:catalogue:manage-orders')->update(['name' => 'tenancy:sales:manage-orders']);
            Permission::where('name', 'tenancy:catalogue:manage-customers')->update(['name' => 'tenancy:sales:manage-customers']);
            Permission::where('name', 'tenancy:catalogue:manage-discounts')->update(['name' => 'tenancy:sales:manage-discounts']);

            foreach (LunarAccessControl::getBasePermissions() as $permission) {
                Permission::firstOrCreate([
                    'name' => $permission,
                    'guard_name' => $guard,
                ]);
            }
        }
    }
}
