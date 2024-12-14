<?php

namespace Tests\Feature;

use Database\Factories\ChirpFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;


class ChirpTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    // test pour permettre à un utilisateur  de créer un chirp
    public function test_un_utilisateur_peut_creer_un_chirp()
    {
        // Simuler un utilisateur connecté
        $utilisateur = User::factory()->create();
        $this->actingAs($utilisateur);

        // Envoyer une requête POST pour créer un chirp
        $reponse = $this->post('/chirps', [
            'message' => 'Mon premier chirp !'
        ]);

        // Vérifier que le chirp a été ajouté à la base de donnée
        $reponse->assertStatus(302);
        $this->assertDatabaseHas('chirps', [
            'message' => 'Mon premier chirp !',
            'user_id' => $utilisateur->id,
        ]);
    }

    // test pour empecher que le contenu d'un chirp soit vide
    public function test_un_chirp_ne_peut_pas_avoir_un_contenu_vide()
    {
        $utilisateur = User::factory()->create();
        $this->actingAs($utilisateur);
        $reponse = $this->post('/chirps', [
            'message' => ''
        ]);
        $reponse->assertSessionHasErrors(['message']);
    }

    // test pour empecher le contenu d'un chirp de dépasser 255 caractère
    public function test_un_chirp_ne_peut_pas_depasse_255_caracteres()
    {
        $utilisateur = User::factory()->create();
        $this->actingAs($utilisateur);
        $reponse = $this->post('/chirps', [
            'message' => str_repeat('a', 256)
        ]);
        $reponse->assertSessionHasErrors(['message']);
    }


    // test pour vérifier si les Chirps s'affichent sur la page d'accueil
    public function test_les_chirps_sont_affiches_sur_la_page_d_accueil()
    {
        $utilisateur = User::factory()->create();
        $this->actingAs($utilisateur);
        $this->assertAuthenticatedAs($utilisateur);

        $chirps = ChirpFactory::new()->count(3)->create(['user_id' => $utilisateur->id]);
        
        $reponse = $this->get('/chirps/index');
        foreach ($chirps as $chirp) {
            $reponse->assertSee($chirp->contenu);
        }
    }

    // test pour permettre à un utilisateur de modifier son propre chirp
    public function test_un_utilisateur_peut_modifier_son_chirp()
    {
        $utilisateur = User::factory()->create();
        $chirp = ChirpFactory::new()->create(['user_id' => $utilisateur->id]);
        $this->actingAs($utilisateur);
        $reponse = $this->put("/chirps/{$chirp->id}", [
            'message' => 'Chirp modifié'
        ]);
        $reponse->assertStatus(302);

        // Vérifie si le chirp existe dans la base de donnée.
        $this->assertDatabaseHas('chirps', [
            'id' => $chirp->id,
            'message' => 'Chirp modifié',
        ]);
    }

    // test pour permettre à un utilisateur de supprimer son propre chirp
    public function test_un_utilisateur_peut_supprimer_son_chirp()
    {
        $utilisateur = User::factory()->create();
        $chirp = ChirpFactory::new()->create(['user_id' => $utilisateur->id]);
        $this->actingAs($utilisateur);

        $reponse = $this->delete("/chirps/{$chirp->id}");
        $reponse->assertStatus(302);
        $this->assertDatabaseMissing('chirps', [
            'id' => $chirp->id,
        ]);
    }

    // test pour empecher un utilsateur de modifier le Chirp d'u autre utilisateur
    public function test_pour_empecher_un_utilisateur_de_modifier_le_chirp_d_un_autre_utilisateur()
    {
        $utilisateur1 = User::factory()->create();
        $utilisateur2 = User::factory()->create();

        $chirp = ChirpFactory::new()->create(['user_id' => $utilisateur1->id]);

        $this->actingAs($utilisateur2);
        $reponse = $this->put("/chirps/{$chirp->id}", [
            'message' => 'Chirp modifié'
        ]);
     
        $reponse->assertStatus(403);
    }

    // test pour empecher un utilsateur de supprimer le Chirp d'u autre utilisateur
    public function test_pour_empecher_un_utilisateur_de_supprimer_le_chirp_d_un_autre_utilisateur()
    {
        $utilisateur1 = User::factory()->create();
        $utilisateur2 = User::factory()->create();

        $chirp = ChirpFactory::new()->create(['user_id' => $utilisateur1->id]);

        $this->actingAs($utilisateur2);
        $reponse = $this->delete("/chirps/{$chirp->id}", [
            'message' => 'Chirp modifié'
        ]);
     
        $reponse->assertStatus(403);
    }


    // test empechant un Chirp d'avoir un contenu vide lors de sa modification
    public function test_un_chirp_ne_peut_pas_avoir_un_contenu_vide_lors_de_sa_modification()
    {
        $utilisateur = User::factory()->create();
        $chirp = ChirpFactory::new()->create(['user_id' => $utilisateur->id]);
        $this->actingAs($utilisateur);
        $reponse = $this->put("/chirps/{$chirp->id}", [
            'message' => ''
        ]);
        $reponse->assertSessionHasErrors(['message']);
    }

    // test pour empecher le contenu d'un Chirp de dépasser 255 caractères lors de sa modifiaction
    public function test_un_chirp_ne_peut_pas_depasse_255_caracteres_lors_de_sa_modification()
    {
        $utilisateur = User::factory()->create();
        $chirp = ChirpFactory::new()->create(['user_id' => $utilisateur->id]);
        $this->actingAs($utilisateur);
        $reponse = $this->put("/chirps/{$chirp->id}", [
            'message' => str_repeat('a', 256)
        ]);
        $reponse->assertSessionHasErrors(['message']);
    }

    // test pour limiter le nombre de Chirp possible de créer à 10 pour un utilisateur
    public function test_limiter_le_nombre_de_chirp_possible_de_creer_a_10_pour_un_utilisateur()
    {
        $utilisateur = User::factory()->create();
        $this->actingAs($utilisateur);
    
        ChirpFactory::new()->count(10)->create(['user_id' => $utilisateur->id]);
    
        $reponse = $this->post('/chirps', [
            'message' => 'Chirp numéro 11',
        ]);
    
        $reponse->assertStatus(403);
    
        $this->assertDatabaseMissing('chirps', [
            'message' => 'Chirp numéro 11',
        ]);
    }

    // test pour afficher uniquemeent les Chirps créé lors des 7 derniers jours
    public function test_afficher_les_chirps_cree_lors_des_7_derniers_jours()
    {
        $utilisateur = User::factory()->create();
        $this->actingAs($utilisateur);

        ChirpFactory::new()->count(5)->create([
            'user_id' => $utilisateur->id,
            'created_at' => now()->subDays(10)
        ]);

        $reponse = $this->get('/chirps');
        $reponse->assertOk();

        $chirps = $reponse->viewData('chirps');
        $this->assertCount(5, $chirps);

        foreach ($chirps as $chirp) {
            $this->assertTrue($chirp->created_at->lessThanOrEqualTo(now()->subDays(7)));
        }

    }

}
