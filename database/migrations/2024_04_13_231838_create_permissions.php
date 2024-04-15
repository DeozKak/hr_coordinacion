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
        Permission::create(['name' => 'gestion_usuarios']);
        $user = User::find(1);
        $user->givePermissionTo('gestion_usuarios','ver coordinacion RP','ver coordinacion RN');
        $user2 = User::find(2);
        $user2->givePermissionTo('ver coordinacion RP');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       
    }
};
