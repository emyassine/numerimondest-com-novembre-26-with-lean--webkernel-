<?php declare(strict_types=1);

use Webkernel\Database\Blueprint;
use Webkernel\Database\Migration;
use Webkernel\Database\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('events', static function (Blueprint $table): void {
            $table->increments();
            $table->string('name');
        });
    }

    public function down(): void
    {
        Schema::drop('events');
    }
};
