# Request Validation Classes - Usage Guide

## Overview
Request validation classes have been created to separate validation logic from controllers, following Laravel best practices.

## Available Request Classes

### Authentication
- **AuthRequest** - Handles login, register, profile update, password change

### Master Data
- **SizeRequest** - Size validation
- **TailorRequest** - Tailor validation
- **BrandRequest** - Brand validation
- **ArticleRequest** - Article validation
- **FabricRequest** - Fabric validation

### Production Flow
- **CuttingResultRequest** - Cutting result validation
- **QCResultRequest** - Quality control validation

### Statistics
- **StatisticsRequest** - Statistics filtering and date range validation

## Usage in Controllers

### Example 1: Basic Usage
```php
use App\Http\Requests\SizeRequest;

public function store(SizeRequest $request)
{
    // Validation is automatically handled before this method executes
    $size = Size::create($request->validated());

    return response()->json([
        'success' => true,
        'data' => $size
    ]);
}
```

### Example 2: Update with Route Model Binding
```php
use App\Http\Requests\TailorRequest;

public function update(TailorRequest $request, Tailor $tailor)
{
    // $tailor is automatically resolved from route
    // Request is validated automatically

    $tailor->update($request->validated());

    return response()->json([
        'success' => true,
        'data' => $tailor->fresh()
    ]);
}
```

### Example 3: Additional Controller Logic
```php
use App\Http\Requests\QCResultRequest;

public function store(QCResultRequest $request)
{
    // Access validated data
    $validated = $request->validated();

    // Additional business logic
    $qcResult = QCResult::create($validated);

    return response()->json([
        'success' => true,
        'data' => $qcResult
    ]);
}
```

## Validation Features

### 1. Automatic Validation
- Validation runs before controller method executes
- Returns JSON error response on failure
- Consistent error format across all endpoints

### 2. Custom Error Messages
```php
// Example from SizeRequest
public function messages(): array
{
    return [
        'name.required' => 'Size name is required.',
        'abbreviation.unique' => 'This abbreviation is already in use.',
    ];
}
```

### 3. Business Logic Validation
```php
// Example from QCResultRequest
public function withValidator($validator)
{
    $validator->after(function ($validator) {
        if ($this->total_to_repair > $this->total_products) {
            $validator->errors()->add('total_to_repair',
                'Total to repair cannot exceed total products.');
        }
    });
}
```

### 4. Data Preparation
```php
// Example from TailorRequest
protected function prepareForValidation(): void
{
    $this->merge([
        'is_active' => $this->has('is_active')
            ? $this->boolean('is_active')
            : true,
    ]);
}
```

## Error Response Format

All validation failures return consistent JSON format:

```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "field_name": [
            "Error message 1",
            "Error message 2"
        ]
    }
}
```

## Benefits

1. **Cleaner Controllers** - Validation logic separated from business logic
2. **Reusable Validation** - Same validation can be used in multiple places
3. **Consistent Responses** - Standardized error handling
4. **Better Testing** - Easy to test validation rules independently
5. **Type Safety** - IDE support for validated data

## Migration Notes

To migrate existing controllers to use request classes:

1. Import the request class
2. Replace `Request $request` with `YourRequest $request`
3. Remove manual validation from controller
4. Use `$request->validated()` instead of `$request->all()`
5. Remove custom error handling (handled by request class)

## Testing Request Classes

Request classes can be tested independently:

```php
use App\Http\Requests\SizeRequest;

public function test_size_validation()
{
    $request = new SizeRequest([
        'name' => 'Test Size',
        'abbreviation' => 'TS',
    ]);

    $this->assertTrue($request->authorize());
    $this->assertNotEmpty($request->rules());
}
```
