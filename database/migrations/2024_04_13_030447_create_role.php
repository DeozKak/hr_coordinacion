<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
/*         $rol1 = Role::create(['name' => 'admin']);
        $rol2 = Role::create(['name'=> 'Coordinador_RP']);
        $rol3 = Role::create(['name'=> 'Coordinador_RN']);
        $rol3 = Role::create(['name' => 'user']);
        $permision1 = Permission::create(['name' => 'ver coordinacion RP']);
        $permision2 = Permission::create(['name' => 'ver coordinacion RN']);
        $user = User::find(1);
        $user->assignRole('admin');
        $rol1->givePermissionTo($permision1,$permision2);
        $rol2->givePermissionTo($permision1);
        $rol3->givePermissionTo($permision2); */
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        
    }
};
