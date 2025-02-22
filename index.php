<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat WebSocket avec Ratchet</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
      .important-message {
          border-left: 4px solid #fbbf24; /* Couleur jaune pour les messages importants */
      }

      .very-important-message {
          border-left: 4px solid #ef4444; /* Couleur rouge pour les messages très importants */
          font-weight: bold;
      }
    </style>
</head>
<body class="bg-gray-100 p-5">
    <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-lg p-6">
        <h1 class="text-3xl font-bold text-center mb-6">Chat WebSocket avec Ratchet</h1>
        
        <!-- Panneau de connexion -->
        <div class="border-b border-gray-200 pb-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Paramètres de connexion</h2>
            <div class="flex flex-wrap gap-4 mb-4">
                <div>
                    <label for="protocol" class="block text-sm font-medium text-gray-700">Protocole:</label>
                    <select id="protocol" class="mt-1 block w-full p-2 border border-gray-300 rounded-md">
                        <option value="wss://">wss://</option>
                        <option value="ws://">ws://</option>
                    </select>
                </div>
                <div>
                    <label for="hostname" class="block text-sm font-medium text-gray-700">Hostname:</label>
                    <input type="text" id="hostname" value="localhost" class="mt-1 block w-full p-2 border border-gray-300 rounded-md">
                </div>
                <div>
                    <label for="port" class="block text-sm font-medium text-gray-700">Port:</label>
                    <input type="number" id="port" value="8080" class="mt-1 block w-20 p-2 border border-gray-300 rounded-md">
                </div>
                <div>
                    <label for="endpoint" class="block text-sm font-medium text-gray-700">Endpoint:</label>
                    <input type="text" id="endpoint" value="/" class="mt-1 block w-20 p-2 border border-gray-300 rounded-md">
                </div>
            </div>
            <div class="flex items-center gap-4">
                <label for="pseudo" class="block text-sm font-medium text-gray-700">Pseudo:</label>
                <input type="text" id="pseudo" placeholder="Entrez votre pseudo" required class="flex-1 p-2 border border-gray-300 rounded-md">
                <button id="connect" class="bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600">Se connecter</button>
                <button id="disconnect" class="bg-red-500 text-white px-4 py-2 rounded-md hover:bg-red-600 disabled:bg-gray-300" disabled>Se déconnecter</button>
            </div>
            <div id="status" class="mt-4 p-2 rounded-md bg-gray-100 text-gray-600">Déconnecté</div>
        </div>
        
        <!-- Panneau de messages -->
        <div>
            <h2 class="text-xl font-semibold mb-4">Messages</h2>
            <div id="messages" class="h-96 overflow-y-auto bg-gray-50 border border-gray-200 rounded-md p-4 mb-4"></div>
            
            <div class="flex gap-4 mb-4">
                <div>
                    <label for="importance" class="block text-sm font-medium text-gray-700">Importance:</label>
                    <select id="importance" class="mt-1 block w-full p-2 border border-gray-300 rounded-md">
                        <option value="normal">Normal</option>
                        <option value="important">Important</option>
                        <option value="very-important">Très important</option>
                    </select>
                </div>
                <div>
                    <label for="color" class="block text-sm font-medium text-gray-700">Couleur:</label>
                    <input type="color" id="color" value="#000000" class="mt-1 block w-12 h-10 p-1 border border-gray-300 rounded-md">
                </div>
            </div>
            
            <div class="flex gap-4">
                <input type="text" id="message" placeholder="Entrez votre message..." disabled class="flex-1 p-2 border border-gray-300 rounded-md">
                <button id="send" disabled class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">Envoyer</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Éléments DOM
            const protocolSelect = document.getElementById('protocol');
            const hostnameInput = document.getElementById('hostname');
            const portInput = document.getElementById('port');
            const endpointInput = document.getElementById('endpoint');
            const pseudoInput = document.getElementById('pseudo');
            const connectButton = document.getElementById('connect');
            const disconnectButton = document.getElementById('disconnect');
            const messagesDiv = document.getElementById('messages');
            const messageInput = document.getElementById('message');
            const sendButton = document.getElementById('send');
            const statusDiv = document.getElementById('status');
            const importanceSelect = document.getElementById('importance');
            const colorInput = document.getElementById('color');
            
            let socket = null;
            
            // Fonction pour se connecter au serveur WebSocket
            function connect() {
                if (pseudoInput.value.trim() === '') {
                    alert('Veuillez entrer un pseudo');
                    return;
                }
                
                const protocol = protocolSelect.value;
                const hostname = hostnameInput.value;
                const port = portInput.value;
                const endpoint = endpointInput.value;
                const wsUrl = `${protocol}${hostname}:${port}${endpoint}`;
                
                try {
                    console.log('Connexion à', wsUrl);
                    socket = new WebSocket(wsUrl);
                    
                    socket.onopen = function() {
                        console.log('Connexion établie');
                        statusDiv.textContent = 'Connecté à ' + wsUrl;
                        statusDiv.className = 'mt-4 p-2 rounded-md bg-green-100 text-green-700';
                        
                        // Activer/désactiver les boutons appropriés
                        connectButton.disabled = true;
                        disconnectButton.disabled = false;
                        messageInput.disabled = false;
                        sendButton.disabled = false;
                        
                        // Envoyer le pseudo au serveur
                        const connectMessage = {
                            type: 'connect',
                            pseudo: pseudoInput.value
                        };
                        socket.send(JSON.stringify(connectMessage));
                    };
                    
                    socket.onmessage = function(event) {
                        const data = JSON.parse(event.data);
                        displayMessage(data);
                    };
                    
                    socket.onclose = function() {
                        console.log('Connexion fermée');
                        disconnect();
                    };
                    
                    socket.onerror = function(error) {
                        console.error('Erreur WebSocket:', error);
                        alert('Erreur de connexion au WebSocket');
                        disconnect();
                    };
                } catch (error) {
                    console.error('Erreur lors de la création de WebSocket:', error);
                    alert('Erreur lors de la création de la connexion WebSocket');
                }
            }
            
            // Fonction pour se déconnecter
            function disconnect() {
                const pseudo = pseudoInput ? pseudoInput.value : 'Anonyme';

                if (socket?.readyState === WebSocket.OPEN) {
                    socket.send(JSON.stringify({
                        type: 'disconnect',
                        pseudo
                    }));

                    socket.close();
                    socket = null;
                }

            // ✅ Vérifier si un message "pseudo a quitté le chat" existe déjà
            const existingMessages = document.querySelectorAll('.system-message');
            const alreadyExists = [...existingMessages].some(msg => msg.textContent.includes(`${pseudo} a quitté le chat.`));

            // ✅ Ajouter le message uniquement s'il n'existe pas
            if (!alreadyExists) {
              const messageDiv = document.createElement('div');
              messageDiv.className = 'system-message bg-red-100 text-red-600 font-semibold p-2 rounded-lg flex items-center';
              
              // Add the disconnect icon
              messageDiv.innerHTML = `
                  <i class="fas fa-sign-out-alt mr-2"></i>
                  ${pseudo} a quitté le chat.
              `;

              document.getElementById('messages').appendChild(messageDiv);
            }

            // Réinitialiser les boutons
            connectButton.disabled = false;
            disconnectButton.disabled = true;
            messageInput.disabled = true;
            sendButton.disabled = true;
        };

            
            // Fonction pour envoyer un message
            function sendMessage() {
                if (!socket || socket.readyState !== WebSocket.OPEN) {
                    alert('Pas de connexion au serveur');
                    return;
                }
                
                const messageText = messageInput.value.trim();
                if (messageText === '') return;
                
                const messageData = {
                    message: messageText,
                    importance: importanceSelect.value,
                    color: colorInput.value
                };
                
                socket.send(JSON.stringify(messageData));
                messageInput.value = '';
            }
            
            // Fonction pour afficher un message
            function displayMessage(data) {
              if (data.type === 'system' && data.message.includes('a quitté le chat')) {
                  // ✅ Vérifier si un message similaire existe déjà
                  const existingMessages = document.querySelectorAll('.system-message');
                  if ([...existingMessages].some(msg => msg.textContent === data.message)) return; // 🚫 Ne pas ajouter si déjà affiché
              }

              const messageDiv = document.createElement('div');
              messageDiv.className = `message ${data.importance || 'normal'} ${data.type === 'system' ? 'system-message bg-gray-200 p-2 rounded-lg mt-2 mb-2' : 'bg-white p-2 rounded-lg mb-2'}`;

              // Ajouter une classe en fonction de l'importance
              if (data.importance === 'important') {
                  messageDiv.classList.add('important-message');
              } else if (data.importance === 'very-important') {
                  messageDiv.classList.add('very-important-message');
              }

              if (data.type === 'system') {
                  messageDiv.innerHTML = `
                      <span class="message-content text-gray-700">${data.message}</span>
                      <span class="time text-gray-500 text-sm">${data.time}</span>
                  `;
              } else {
                  // Appliquer la couleur du texte
                  messageDiv.innerHTML = `
                      <div class="flex items-start mb-2">
                          <strong class="text-blue-600 mr-2">${data.pseudo}</strong>
                          <span class="time text-gray-500 text-xs">${data.time}</span>
                      </div>
                      <span class="message-content rounded-lg p-2 shadow-md" style="color: ${data.color}">${data.message}</span>
                  `;
              }

              messagesDiv.appendChild(messageDiv);
              messagesDiv.scrollTop = messagesDiv.scrollHeight;
            }

            
            // Événements
            connectButton.addEventListener('click', connect);
            disconnectButton.addEventListener('click', disconnect);
            sendButton.addEventListener('click', sendMessage);
            
            messageInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    sendMessage();
                }
            });
        });
    </script>
</body>
</html>