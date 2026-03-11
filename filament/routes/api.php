<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ApisController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\CommandeController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| All routes replicate the legacy backend/ API endpoints exactly,
| so the Next.js frontend continues working without changes.
|
*/

// ── Cached Public Routes (5 min TTL) ─────────────────
// These endpoints serve static/semi-static content
Route::middleware(['cache.api:300', 'cache.headers.api:300'])->group(function () {
    Route::get('/accueil', [ApisController::class, 'accueil']);
    Route::get('/home', [ApisController::class, 'home']);
    Route::get('/categories', [ApisController::class, 'categories']);
    Route::get('/slides', [ApisController::class, 'slides']);
    Route::get('/coordonnees', [ApisController::class, 'coordonnees']);
    Route::get('/media', [ApisController::class, 'media']);
    Route::get('/all_brands', [ApisController::class, 'allBrands']);
    Route::get('/aromes', [ApisController::class, 'aromes']);
    Route::get('/tags', [ApisController::class, 'tags']);
    Route::get('/services', [ApisController::class, 'services']);
    Route::get('/faqs', [ApisController::class, 'faqs']);
    Route::get('/pages', [ApisController::class, 'pages']);
    Route::get('/redirections', [ApisController::class, 'redirections']);
    Route::get('/latest_articles', [ApisController::class, 'latestArticles']);
    Route::get('/latest_products', [ApisController::class, 'latestProducts']);
    Route::get('/latest_packs', [ApisController::class, 'latestPacks']);
    Route::get('/new_product', [ApisController::class, 'newProduct']);
    Route::get('/best_sellers', [ApisController::class, 'bestSellers']);
    Route::get('/packs', [ApisController::class, 'packs']);
    Route::get('/ventes_flash', [ApisController::class, 'flash']);
});

// ── Short-cached Public Routes (1 min TTL) ───────────
// These depend on dynamic parameters but can still be cached briefly
// NOTE: Compression removed from PHP level - use Nginx gzip instead (faster)
Route::middleware(['cache.api:60', 'cache.headers.api:60'])->group(function () {
    Route::get('/product_details/{slug}', [ApisController::class, 'productDetails']);
    Route::get('/article_details/{slug}', [ApisController::class, 'articleDetails']);
    Route::get('/productsByCategoryId/{slug}', [ApisController::class, 'productsByCategoryId']);
    Route::get('/productsByBrandId/{brand_id}', [ApisController::class, 'productsByBrandId']);
    Route::get('/productsBySubCategoryId/{slug}', [ApisController::class, 'productsBySubCategoryId']);
    Route::get('/similar_products/{sous_categorie_id}', [ApisController::class, 'similar_products']);
    Route::get('/seo_page/{name}', [ApisController::class, 'seoPage']);
    Route::get('/page/{slug}', [ApisController::class, 'getPageBySlug']);
    Route::get('/all_products', [ApisController::class, 'allProducts']);
    Route::get('/all_articles', [ApisController::class, 'allArticles']);
});

// ── FAST ROUTE (Minimal middleware) — SUPPRIMÉE pour raison de sécurité ─────
// Cette route contournait throttle:api et exposait /all_products sans limitation.
// Voir audit sécurité v2 (rapport_audit_v2_sobitas.pdf, finding #10).

// ── Uncached Public Routes (dynamic/write operations) ─
Route::get('/searchProduct/{text}', [ApisController::class, 'searchProduct']);
Route::get('/searchProductBySubCategoryText/{slug}/{text}', [ApisController::class, 'searchProductBySubCategoryText']);

Route::post('/add_commande', [CommandeController::class, 'storeCommandeApi']);
Route::post('/newsletter', [ApisController::class, 'newsLetter'])->middleware('throttle:3,1');
Route::post('/contact', [ApisController::class, 'sendContact'])->middleware('throttle:3,1');
Route::post('/send_mail', [ApisController::class, 'send_email'])->middleware('throttle:3,1');

// Auth — strict rate limits to prevent brute-force
Route::post('/login', [ClientController::class, 'login'])->middleware('throttle:5,1');
Route::post('/register', [ClientController::class, 'register'])->middleware('throttle:10,1');

// ── Authenticated Routes ──────────────────────────────
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profil', [ClientController::class, 'profil']);
    Route::get('/commande/{id}', [CommandeController::class, 'details']); // moved here — IDOR fix
    Route::get('/client_commandes', [ClientController::class, 'client_commandes']);
    Route::post('/update_profile', [ClientController::class, 'update_profile']);
    Route::post('/detail_commande/{id}', [ClientController::class, 'detail_commande']);
    Route::post('/add_review', [ApisController::class, 'add_review']);
});
