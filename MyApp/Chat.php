<?php
namespace MyApp;
// Serveur WebSocket utilisant Ratchet
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;



require_once 'vendor/autoload.php';

class Chat implements MessageComponentInterface {
    protected $clients;
    protected $users = [];

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        echo "Serveur démarré!\n";
    }

    public function onOpen(ConnectionInterface $conn) {
        // Stocker la nouvelle connexion pour envoyer des messages plus tard
        $this->clients->attach($conn);
        echo "Nouvelle connexion! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
      $data = json_decode($msg, true);
      
      // Si c'est le premier message, c'est probablement une connexion avec pseudo
      if (isset($data['type']) && $data['type'] === 'connect') {
          $this->users[$from->resourceId] = $data['pseudo'];
          $message = [
              'type' => 'system',
              'message' => $data['pseudo'] . ' a rejoint le chat',
              'importance' => 'normal',
              'color' => '#000000',
              'time' => date('H:i:s')
          ];
      } else {
          // Message normal
          $message = [
              'type' => 'message',
              'pseudo' => $this->users[$from->resourceId] ?? 'Anonyme',
              'message' => $data['message'],
              'importance' => $data['importance'] ?? 'normal',
              'color' => $data['color'] ?? '#000000',
              'time' => date('H:i:s')
          ];
      }

      $messageJson = json_encode($message);
      echo "Message reçu de {$from->resourceId}: {$msg}\n";
      echo "Message envoyé: {$messageJson}\n";

      // Envoyer à tous les clients connectés
      foreach ($this->clients as $client) {
          $client->send($messageJson);
      }
  }
  
    public function onClose(ConnectionInterface $conn) {
        // La connexion a été fermée, supprimer l'objet
        $this->clients->detach($conn);
        
        // Informer les autres du départ
        if (isset($this->users[$conn->resourceId]) ) {
            $message = [
                'type' => 'system',
                'message' => $this->users[$conn->resourceId] . ' a quitté le chat',
                'importance' => 'normal',
                'color' => '#000000',
                'time' => date('H:i:s')
            ];
            
            $messageJson = json_encode($message);
            
            foreach ($this->clients as $client) {
                $client->send($messageJson);
            }
            
            // Supprimer l'utilisateur de la liste
            unset($this->users[$conn->resourceId]);
        }
        
        echo "Connexion {$conn->resourceId} fermée\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Une erreur est survenue: {$e->getMessage()}\n";
        $conn->close();
    }
}

