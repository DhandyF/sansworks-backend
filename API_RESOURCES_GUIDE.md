# API Resources - Usage Guide

## Overview
Laravel API Resources provide a consistent way to transform models into JSON responses, giving you full control over data exposure and formatting.

## Available Resources

### Core Resources
- **UserResource** - User data transformation
- **SizeResource** - Size data transformation
- **TailorResource** - Tailor data transformation
- **BrandResource** - Brand data transformation
- **ArticleResource** - Article data transformation
- **FabricResource** - Fabric data transformation (with computed total_price)
- **CuttingResultResource** - Cutting result data transformation
- **QCResultResource** - Quality control data transformation (with computed pass_rate)

### Utility Resources
- **SuccessResponseResource** - Standardized success responses

## Usage Examples

### 1. Single Resource Response
```php
use App\Http\Resources\SizeResource;
use App\Models\Size;

public function show(Size $size)
{
    return new SizeResource($size);
}

// Response:
{
    "data": {
        "id": 1,
        "name": "Medium",
        "abbreviation": "M",
        "sort_order": 3,
        "is_active": true,
        "created_at": "2024-01-01T00:00:00.000000Z",
        "updated_at": "2024-01-01T00:00:00.000000Z"
    },
    "success": true
}
```

### 2. Collection Response
```php
use App\Http\Resources\SizeResource;

public function index()
{
    $sizes = Size::all();
    return SizeResource::collection($sizes);
}

// Response:
{
    "data": [
        {
            "id": 1,
            "name": "Extra Small",
            "abbreviation": "XS",
            ...
        },
        {
            "id": 2,
            "name": "Small",
            "abbreviation": "S",
            ...
        }
    ],
    "success": true
}
```

### 3. Resource with Relationships
```php
use App\Http\Resources\CuttingResultResource;

public function show(CuttingResult $cuttingResult)
{
    // Load relationships
    $cuttingResult->load(['fabric', 'brand', 'article', 'size']);

    return new CuttingResultResource($cuttingResult);
}

// Response includes relationships when loaded:
{
    "data": {
        "id": 1,
        "fabric_id": 1,
        "brand_id": 1,
        "fabric": {
            "id": 1,
            "name": "Katun Primisima",
            ...
        },
        "brand": {
            "id": 1,
            "name": "Fashion Forward",
            ...
        },
        ...
    },
    "success": true
}
```

### 4. Computed Fields
```php
// FabricResource automatically computes total_price
{
    "data": {
        "id": 1,
        "name": "Katun Primisima",
        "total_quantity": 500.0,
        "price_per_unit": 45000.0,
        "total_price": 22500000.0,  // Computed: quantity * price
        ...
    }
}

// QCResultResource automatically computes pass_rate
{
    "data": {
        "id": 1,
        "total_products": 100,
        "total_to_repair": 5,
        "pass_rate": 95.0,  // Computed: (100 - 5) / 100 * 100
        ...
    }
}
```

### 5. Pagination
```php
use App\Http\Resources\SizeResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

public function index(): AnonymousResourceCollection
{
    $sizes = Size::paginate(15);
    return SizeResource::collection($sizes);
}

// Response includes pagination metadata:
{
    "data": [...],
    "success": true,
    "links": {
        "first": "...?page=1",
        "last": "...?page=3",
        "prev": null,
        "next": "...?page=2"
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 3,
        "per_page": 15,
        "to": 15,
        "total": 45
    }
}
```

### 6. Conditional Data
```php
// Only include relationship when loaded
'fabric' => $this->whenLoaded('fabric'),

// Only include value if condition is met
'admin_notes' => $this->when(auth()->user()->isAdmin(), $this->admin_notes),
```

### 7. Custom Response
```php
use App\Http\Resources\SuccessResponseResource;

public function store(Request $request)
{
    $size = Size::create($request->validated());

    return new SuccessResponseResource(
        $size,
        'Size created successfully',
        201
    );
}

// Response:
{
    "success": true,
    "message": "Size created successfully",
    "data": {
        "id": 1,
        "name": "Medium",
        ...
    }
}
```

## Key Features

### 1. Data Transformation
- Convert model data to JSON format
- Format dates as ISO 8601 strings
- Cast numeric values appropriately
- Hide sensitive fields (passwords, tokens)

### 2. Conditional Relationships
```php
// Only include relationships when loaded to avoid N+1 queries
'fabric' => $this->whenLoaded('fabric'),
'created_by_user' => $this->whenLoaded('createdBy'),
```

### 3. Computed Fields
```php
// Automatic calculations
'total_price' => (float) ($this->total_quantity * $this->price_per_unit),
'pass_rate' => $this->total_products > 0
    ? round((($this->total_products - $this->total_to_repair) / $this->total_products) * 100, 2)
    : 100,
```

### 4. Consistent Response Format
```php
// Every response includes success flag
public function with(Request $request): array
{
    return [
        'success' => true,
    ];
}
```

### 5. Type Safety
- All numeric values cast to appropriate types
- Dates formatted consistently
- Null values handled properly

## Benefits

1. **Consistency** - Uniform API responses across all endpoints
2. **Security** - Control exactly what data is exposed
3. **Performance** - Avoid over-fetching data
4. **Maintainability** - Centralized data transformation logic
5. **Documentation** - Self-documenting API structure
6. **Flexibility** - Easy to add/remove fields or computed values

## Best Practices

### 1. Always Use Resources for API Responses
```php
// Good
return new SizeResource($size);

// Avoid
return response()->json($size->toArray());
```

### 2. Load Relationships Before Creating Resources
```php
// Good - prevents N+1 queries
$size->load('createdBy', 'updatedBy');
return new SizeResource($size);

// Avoid - causes N+1 queries
return new SizeResource($size);
```

### 3. Use Collections for Multiple Items
```php
// Good
return SizeResource::collection($sizes);

// Avoid
return $sizes->map(fn($size) => new SizeResource($size));
```

### 4. Add Computed Fields in Resources
```php
// Good - computed in resource
'total_price' => (float) ($this->total_quantity * $this->price_per_unit),

// Avoid - computed in controller
$fabric->total_price = $fabric->total_quantity * $fabric->price_per_unit;
return new FabricResource($fabric);
```

## Migration Notes

To migrate existing controllers to use resources:

1. Import the appropriate resource class
2. Replace `response()->json()` with resource instantiation
3. Remove manual data transformation
4. Use `::collection()` for multiple items
5. Load relationships before creating resources

Example migration:
```php
// Before
public function index()
{
    $sizes = Size::all();
    return response()->json([
        'success' => true,
        'data' => $sizes
    ]);
}

// After
public function index()
{
    $sizes = Size::all();
    return SizeResource::collection($sizes);
}
```
