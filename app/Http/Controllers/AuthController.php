<?php




namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Connexion de l'utilisateur.
     */
    public function login(Request $request)
    {
        // 1. Validation de la forme des données
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // 2. Récupération de l'utilisateur
        $user = User::where('email', $request->email)->first();

        // 3. Vérification des identifiants (Email existe + Mot de passe correct)
        // IMPORTANT : On retourne du JSON directement (401) au lieu d'une Exception
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Les identifiants fournis sont incorrects.',
                'errors' => [
                    'email' => ['Les identifiants fournis sont incorrects.']
                ]
            ], 401); // Code 401 Unauthorized
        }

        // 4. Création du token (Sanctum Bearer Token)
        $token = $user->createToken('auth_token')->plainTextToken;

        // 5. Réponse JSON Succès
        return response()->json([
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 200);
    }

    /**
     * Récupérer l'utilisateur connecté.
     */
    public function user(Request $request)
    {
        // Sanctum protège automatiquement cette route si 'auth:sanctum' est appliqué
        return response()->json($request->user());
    }

    /**
     * Déconnexion.
     */
    public function logout(Request $request)
    {
        // Révocation du token
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Déconnexion réussie.',
        ]);
    }
}
