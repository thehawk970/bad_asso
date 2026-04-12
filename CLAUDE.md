# BadManager — CLAUDE.md

Application de gestion de club de badminton. Laravel 13 + Filament v5 + React 19/TypeScript + PostgreSQL.

---

## Stack

| Couche | Technologie |
|---|---|
| Backend | Laravel 13, PHP 8.3 |
| Admin UI | Filament v5 |
| Frontend | React 19, TypeScript, Inertia.js |
| Base de données | PostgreSQL |
| Conteneurs | Docker Compose (`apps/backend/compose.yml`) |

---

## Architecture métier

### Modèles principaux

- **Player** — joueur du club. À la création, une licence `pending` est automatiquement créée pour la saison active.
- **Season** — saison sportive (01/09 → 31/08, format `"25-26"`). Une seule saison active à la fois. `Season::current()` retourne la saison active.
- **License** — lie un joueur à une saison. 4 conditions booléennes à remplir pour valider :
  - `payment_confirmed`
  - `health_form_filled`
  - `info_form_filled`
  - `rules_signed`
- **Product** — produit du catalogue (ex : licence 75 €, volant). `is_license_product` indique si le produit déclenche la création/validation d'une licence.
- **Order** — commande d'un joueur. Contient des `OrderItem`. Le total est calculé depuis les items (snapshot de prix).
- **OrderItem** — ligne de commande. `unit_price` est un snapshot du prix au moment de la commande, indépendant du catalogue.
- **Payment** — paiement lié à un joueur et optionnellement à une commande (`order_id` nullable). `method` nullable (défini lors de la validation, pas à la création).

### Statuts

| Modèle | Enum | Valeurs |
|---|---|---|
| Order | `OrderStatus` | `pending`, `paid`, `cancelled` |
| Payment | `PaymentStatus` | `pending`, `validated` |
| License | `LicenseStatus` | `pending`, `in_progress`, `validated` |

---

## Services (`app/Services/`)

Toute la logique métier est dans les services. **Filament ne fait que collecter les données et appeler les services.**

### `OrderService`
- `handleAfterCreate(Order, LicenseService)` — recalcule le total, crée un paiement `pending` par article, crée une licence en attente si applicable
- `recalculateTotal(Order)` — recalcule `total` depuis les `OrderItem`
- `markAsPaid(Order, PaymentMethod)` — valide tous les paiements en attente avec la méthode donnée, passe la commande en `paid` (l'`OrderObserver` prend le relais pour la licence)
- `checkIfFullyPaid(Order)` — si la somme des paiements validés ≥ total, passe la commande en `paid`

### `LicenseService`
- `ensurePendingLicenseForOrder(Order)` — crée une licence `pending` si la commande contient un produit licence et qu'aucune n'existe
- `confirmPaymentForOrder(Order)` — met `payment_confirmed = true` sur la licence, tente la validation
- `createPendingForPlayer(Player, Season)` — crée une licence `pending`
- `renewForPlayer(Player, Season)` — renouvelle la licence (retourne `null` si elle existe déjà)
- `updateConditionsAndValidate(License, array)` — met à jour les conditions puis appelle `checkAndValidate()`

### `PaymentService`
- `validate(Payment, PaymentMethod)` — passe le paiement en `validated` avec la méthode (le `PaymentObserver` déclenche ensuite `checkIfFullyPaid`)

### `SeasonService`
- `createAndActivate(array)` — crée une saison et l'active (retourne `null` si le nom existe déjà)

---

## Observers (`app/Observers/`)

Enregistrés via l'attribut `#[ObservedBy]` directement sur les modèles.

### `PaymentObserver`
- `updated` — si le statut passe à `validated` et qu'un `order_id` existe → `OrderService::checkIfFullyPaid()`
- `deleted` — si un paiement validé est supprimé et que la commande était `paid` → repasse en `pending`

### `OrderObserver`
- `updated` — si le statut passe à `paid` → `LicenseService::confirmPaymentForOrder()`

---

## Filament v5 — conventions importantes

### Namespaces consolidés
```php
// Schemas (pas Filament\Forms\Form)
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;  // pas Filament\Forms\Set

// Actions (toutes dans Filament\Actions\*)
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\CreateAction;  // y compris dans les RelationManagers
```

### Recherche de joueur (pattern obligatoire)
Ne pas utiliser `->relationship('player', ...)` avec `getOptionLabelFromRecordUsing` — incompatible v5.
Utiliser systématiquement :
```php
Select::make('player_id')
    ->getSearchResultsUsing(fn (string $search) => Player::where('last_name', 'ilike', "%{$search}%")
        ->orWhere('first_name', 'ilike', "%{$search}%")
        ->limit(50)->get()
        ->mapWithKeys(fn (Player $p) => [$p->id => $p->last_name . ' ' . $p->first_name])
    )
    ->getOptionLabelUsing(fn ($value) => Player::find($value)?->full_name ?? '—')
    ->searchable()
```

### Actions sur les lignes de table
Utiliser `->recordActions([...])` et non `->actions([...])` (deprecated en v5).

### Actions de la barre d'outils (bulk actions)
Utiliser `->toolbarActions([...])` et non `->bulkActions([...])` (deprecated en v5).

### Mutation des données dans les Actions
Utiliser `->mutateDataUsing(...)` et non `->mutateFormDataUsing(...)` (deprecated en v5).

### Schema dans les modales d'Action
Utiliser `->schema([...])` et non `->form([...])` (deprecated en v5).

### Infolist au niveau resource
Déclarer `infolist(Schema $schema)` sur le `Resource`, pas sur la page `ViewXxx`.

### Dates formatées
Ne jamais utiliser `->dateTime()->default('—')` (Carbon ne parse pas `'—'`).
Utiliser `->formatStateUsing(fn ($state) => $state?->format('d/m/Y H:i') ?? '—')`.

### Valeurs nullables dans les badges
Toujours utiliser l'opérateur null-safe :
```php
->getStateUsing(fn (Payment $record) => $record->method?->label() ?? '—')
```

---

## Règles métier clés

1. **Prix snapshot** — `unit_price` sur `order_items` est copié depuis le catalogue à la sélection. Il n'évolue pas si le produit change de prix.

2. **Multi-paiement** — une commande peut avoir plusieurs paiements (`payments.order_id`). La commande passe en `paid` quand la somme des paiements `validated` ≥ `total`.

3. **Méthode de paiement** — uniquement sur `Payment`, jamais sur `Order`. Les paiements sont créés sans méthode (`null`) à la création de la commande.

4. **Validation de licence** — déclenchée automatiquement par `LicenseService::updateConditionsAndValidate()` ou `confirmPaymentForOrder()`. Les 4 conditions doivent toutes être `true`.

5. **Produit licence** — un produit avec `is_license_product = true` déclenche la création d'une licence à la commande et la confirmation du paiement quand la commande est payée.

6. **Saison active** — une seule à la fois. `Season::activate()` désactive toutes les autres. `Season::current()` retourne la saison active ou `null`.

---

## Commandes utiles

```bash
# Entrer dans le container app
docker compose -f apps/backend/compose.yml exec app bash

# Lancer les migrations
docker compose -f apps/backend/compose.yml exec app php artisan migrate

# Vider les caches (si OPcache/config bloque)
docker compose -f apps/backend/compose.yml exec app php artisan config:clear
docker compose -f apps/backend/compose.yml restart app
```

---

## Structure des dossiers backend

```
app/
├── Enums/          # LicenseStatus, OrderStatus, PaymentMethod, PaymentStatus
├── Models/         # Modèles Eloquent (relations, scopes, accessors uniquement)
├── Observers/      # OrderObserver, PaymentObserver
├── Services/       # LicenseService, OrderService, PaymentService, SeasonService
└── Filament/
    └── Resources/  # UI admin uniquement — appelle les services, pas de logique métier
```
