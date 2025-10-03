# API EcoGarden // Mission OpenClassRooms

Les routes sont à tester à cette adresse : http://127.0.0.1:8000/api/

## Étape 2 — Les routes

## Liste des routes

- [ ] POST /api/user
- [ ] POST /api/auth

- [x] GET /api/conseil/{mois}
- [x] GET /api/conseil (mois en cours)
- [ ] GET /api/meteo/{ville}
- [ ] GET /api/meteo (ville de l'utilisateur)

- [ ] POST /api/conseil
- [ ] PUT /api/conseil/{id}
- [ ] DELETE /api/conseil/{id}
- [ ] PUT /api/user/{id}
- [ ] DELETE /api/user/{id}

## Exemple de paramètres à envoyer sur les routes

### Pour toutes les routes
Headers :
| Content-Type | application/json |

## Liste de choses à faire

### IMPORTANT
- [x] Faire un HTTP GET pour /api/conseil/{id}
- [ ] Construire les routes CRUD pour la route /api/user

### BONUS
**Fixtures**
- [ ] Revoir la création de données en ce qui concerne les `month` des entités `advice`. Plus logique d'avoir des mois qui se suivent pour les conseils (trimestres, saisons, etc...)