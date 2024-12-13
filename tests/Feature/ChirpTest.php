<?php

namespace Tests\Feature;

use Database\Factories\ChirpFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Chirp;


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
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->assertAuthenticatedAs($user);

        $chirps = ChirpFactory::new()->count(3)->create(['user_id' => $user->id]);
        
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
}
