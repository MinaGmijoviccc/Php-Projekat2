<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;


//Book je roditeljska klasa, a review je dete klasa
//Knjiga ima vise reviewa
class Book extends Model
{
    use HasFactory;

    //Ova funkcija nam govori da jedna knjiga moze da ima vise reviews-a
    public function  reviews ()
    {
        return $this->hasMany(Review::class);
    }
    //Lokalni kveri, Ovo nam omogucava da pisemo krace upite u tinkeru jer smo vec ovde definisali za naslov
//Ovaj kod predstavlja definiciju "scope" funkcije u Laravelu.
// "Scope" je funkcionalnost u Laravelu koja vam omogućava da definišete zajedničke upite za baze podataka koje možete koristiti na različitim mestima u vašem kodu.
// U vašem slučaju, scopeTitle funkcija definiše takav "scope" za pretragu po naslovu.
//Builder $query označava da funkcija prima builder objekat za upit kao prvi argument. Builder objekat omogućava vam da kreirate SQL upite za vašu tabelu.
//: Builder označava da će funkcija vratiti builder objekat kao rezultat.
    public function scopeTitle(Builder $query, string $title): Builder
    {
        return  $query->where('title', 'LIKE', '%' . $title . '%');
    }

    public function scopeWithReviewsCount(Builder $query, $from = null, $to = null): Builder | QueryBuilder
    {
        return $query->withCount([
            'reviews' => fn(Builder $q) => $this->dateRangeFilter($q, $from, $to)
        ]);
    }

    public function scopeWithAvgRating(Builder $query, $from = null, $to = null): Builder | QueryBuilder
    {
        return $query->withAvg([
            'reviews' => fn(Builder $q) => $this->dateRangeFilter($q, $from, $to)
        ], 'rating');
    }

// Ovo je za dateRangeFilter funkciju   public function scopePopular(Builder $query, $from = null, $to = null): Builder | QueryBuilder: Ovo je definicija funkcije. Funkcija se naziva scopePopular i prima tri parametra:
//$query: Ovaj parametar predstavlja upit (query) koji će se primijeniti na bazu podataka. Tip ovog parametra je Builder, što znači da se očekuje da će biti objekt tipa Builder ili nekog podklase, koji se koristi za izgradnju SQL upita.
//$from: Opcionalni parametar koji predstavlja datum "od" (from). Ako nije proslijeđen, pretpostavlja se da je null.
//$to: Opcionalni parametar koji predstavlja datum "do" (to). Ako nije proslijeđen, pretpostavlja se da je null.
//return $query->withCount([...]): Ovo je početak upita koji će se izgraditi. Poziva se metoda withCount na objektu $query, koja omogućava brojanje povezanih objekata u relacijama.
//'reviews' => function(Builder $q) use ($from, $to) { ... }: Ovdje se definira povezana relacija 'reviews' i funkcija koja će se izvršiti unutar withCount. Unutar ove funkcije, koristi se parametar $q, koji predstavlja novi objekt Builder za pod-upit. Parametri $from i $to su dostupni unutar ove funkcije putem use ($from, $to), što omogućava pristup tim varijablama unutar anonimne funkcije.
//U unutar funkcije definirane su tri različite situacije (if-elseif-else) ovisno o tome jesu li proslijeđeni parametri $from i $to. Ovisno o tim parametrima, odabire se odgovarajući uvjet za filtriranje recenzija u pod-upitu.
//Ako je definiran samo $from, postavlja se uvjet da se recenzije traže nakon ili na datumu $from.
//Ako je definiran samo $to, postavlja se uvjet da se recenzije traže prije ili na datumu $to.
//Ako su definirani i $from i $to, postavlja se uvjet da se recenzije traže između datuma $from i $to.
//Na kraju, poziva se orderBy metoda na glavnom upitu ($query) kako bi se rezultati sortirali prema broju recenzija ('reviews_count') u silaznom redoslijedu ('desc').
    //Ovde se pozviva dateRangeFilter funkcija
    public function scopePopular(Builder $query, $from = null, $to = null):Builder | QueryBuilder
    {
        return $query->withReviewsCount()
            ->orderBy('reviews_count', 'desc');
//        return $query->withCount([
//            'reviews' => fn(Builder $q) => $this->dateRangeFilter($q, $from, $to)
//        ])
//            ->orderBy('reviews_count', 'desc');

    }
    public function scopeHighestRated(Builder $query, $from=null, $to=null): Builder | QueryBuilder
    {

        return $query->withAvgRating()
            ->irderBy('reviews_avg_rating', 'desc');

//        return $query->withAvg([
//            'reviews' => fn(Builder $q) => $this->dateRangeFilter($q, $from, $to)
//        ], 'rating')
//            ->orderBy('reviews_avg_rating', 'desc');
    }
//WHERE: Klauzula WHERE koristi se za postavljanje uvjeta za filtriranje redova iz tablice prije nego što se rezultati vrate iz baze podataka. To znači da WHERE filtrira redove prije nego što se grupiraju (ako se koristi GROUP BY) ili prije nego što se primijeni funkcija agregacije (npr. COUNT, SUM) na grupirane podatke.
//HAVING: Klauzula HAVING koristi se za postavljanje uvjeta za filtriranje rezultata nakon što su podaci grupirani pomoću GROUP BY. To znači da se HAVING primjenjuje na agregirane rezultate nakon što su grupirani, omogućujući vam da filtrirate rezultate temeljem rezultata funkcija agregacije.

    //FUNKCIJE AGREGACIJE SU: count, sum, avg, min, max...
    public function scopeMinReviews(Builder $query, int $minReviews): Builder | QueryBuilder
    {
        return $query->having('reviews_count', '>=', $minReviews);
    }
    private function  dateRangeFilter(Builder $query, $from =null, $to=null)
    {
        if($from && !$to) {
            $query->where('created_at', '>=', $from);
        }
        elseif (!$from &&$to) {
            $query->where('created_at', '<=', $to);
        }
        elseif ($from && $to) {
            $query->whereBetween('created_at', [$from, $to]);
        }
    }
    public function scopePopularLastMonth(Builder $query): Builder|QueryBuilder
    {
        return $query->popular(now()->subMonth(), now())
            ->highestRated(now()->subMonth(),now())
            ->minReviews(2);
    }
    public function scopePopularLast6Months(Builder $query): Builder|QueryBuilder
    {
        return $query->popular(now()->subMonths(6), now())
            ->highestRated(now()->subMonth(6),now())
            ->minReviews(5);
    }
    public function scopeHighestRatedLastMonth(Builder $query): Builder|QueryBuilder
    {
        return $query->highestRated(now()->subMonth(), now())
            ->popular(now()->subMonth(),now())
            ->minReviews(2);
    }
    public function scopeHighestRatedLast6Months(Builder $query): Builder|QueryBuilder
    {
        return $query->highestRated(now()->subMonths(6), now())
            ->popular(now()->subMonths(6),now())
            ->minReviews(5);
    }

    protected static function booted()
    {
        static::updated(fn(Book $book) => cache()->forget('book:' . $book->id));
        static::deleted(fn(Book $book) => cache()->forget('book:' . $book->id));
    }
}
