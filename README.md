# API EcoGarden // Mission OpenClassRooms

Les routes sont à tester à cette adresse : http://127.0.0.1:8000/api/ via POSTMAN par exemple.
<!-- Clé API OpenWeather : 7931eede7141d3223eefcb53948817d0
Clé Lexik JWT : password -->

## Liste des routes

- [x] POST /api/user
- [x] POST /api/auth
- [ ] PUT /api/user/{id}
- [ ] DELETE /api/user/{id}

- [x] GET /api/conseil/{mois}
- [x] GET /api/conseil (mois en cours)
- [ ] POST /api/conseil
- [ ] PUT /api/conseil/{id}
- [ ] DELETE /api/conseil/{id}

- [ ] GET /api/meteo/{ville}
- [ ] GET /api/meteo (ville de l'utilisateur)

## Exemple de paramètres à envoyer sur les routes

### Pour toutes les routes
Headers :

| Content-Type | application/json |
|---|---|
| Authorization | bearer votreTokenDidentificationSiBesoin |

### Pour toutes les routes nécessitant une authentification
Headers :

| Content-Type | application/json |
|---|---|
| Authorization | bearer votreTokenDidentification |

### Pour les routes d'authentification

**POST /api/user**

Body :

```json
{
    "username": "votre@adresse.mail",
    "password": "m0t_dePasse",
    "zip_code": 12345
}
```

## Liste de choses à faire

### IMPORTANT
- [x] Faire un HTTP GET pour /api/conseil/{mois}
- [x] Hachage des mots de passe quand un utilisateur est crée
- [ ] Construire les routes POST, DELETE, PUT pour la route /api/conseil

### BONUS
**Fixtures**
- [ ] Créer un fichier EcoGarden.sql pour insérer les données de la table `month`
- [ ] Revoir la création de données en ce qui concerne les `month` des entités `advice`. Plus logique d'avoir des mois qui se suivent pour les conseils (trimestres, saisons, etc...)
- [ ] Utiliser faker pour avoir des données plus cohérentes