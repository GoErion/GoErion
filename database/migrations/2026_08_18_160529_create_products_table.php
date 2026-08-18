<?php
declare(strict_types=1);

use App\Models\Company\Branch;
use App\Models\Company\Company;
use App\Models\Product\Brand;
use App\Models\Product\Category;
use App\Models\Product\Unit;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignIdFor(Company::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Category::class)->constrained()->restrictOnDelete();
            $table->foreignIdFor(Unit::class)->constrained()->restrictOnDelete();
            $table->foreignIdFor(Brand::class)->nullable()->constrained()->nullOnDelete();
            $table->string('sku'); //Stock Keeping Unit for internal unique code for identifying a specific product.
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('product_type')->default('physical');
            $table->boolean('track_stock')->default(true);
            $table->boolean('track_serial')->default(false);
            $table->boolean('track_batch')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['company_id','sku']);
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
