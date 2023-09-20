<?php

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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            //Ova kolona ce biti strani kljuc, ali ovo je samo kolona, jos uvek nije strani kljuc
          //  $table->unsignedBigInteger('book_id');

            $table->text('review');
            $table->unsignedTinyInteger('rating');

            $table->timestamps();

            //Sad se ovi pravi strani kljuc, znaci imamo kolonu book_id koja se referencira na id iz booksa i tako se pravi strani kluc
//            $table->foreign('book_id')->references('id')->on('books')
//                ->onDelete('cascade');

            //Ovo zamenjuje ove prethodne dve zakomentarisane linije koda
            $table->foreignId('book_id')->constrained()
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
