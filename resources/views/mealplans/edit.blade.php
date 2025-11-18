<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-600 leading-tight">
            {{ __('Edit Weekly Meal Plan') }}
        </h2>
    </x-slot>

    <div class="py-4 max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="card shadow-sm">
            <div class="card-body p-4">

                <form method="POST" action="{{ route('mealplans.update', $mealPlan) }}">
                    @csrf
                    @method('PUT')

                    <input type="hidden" id="week_start" name="week_start" value="{{ $mealPlan->week_start }}" required>

                    <div class="flex justify-between items-center mb-4">
                        <h5 class="fw-semibold mb-0">Editing Week: 
                            <span id="weekRange" class="badge bg-warning text-dark">
                                {{ \Carbon\Carbon::parse($mealPlan->week_start)->format('Y-m-d') }} &mdash; 
                                {{ \Carbon\Carbon::parse($mealPlan->week_start)->addDays(6)->format('Y-m-d') }}
                            </span>
                        </h5>
                    </div>

                    <hr class="mb-4">

                    <div id="weeklyPlan" class="mt-4">
                        <h5 class="fw-semibold mb-3">Update Meals (7 Days)</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Day</th>
                                        <th>Breakfast</th>
                                        <th>Lunch</th>
                                        <th>Dinner</th>
                                        <th>Snack</th>
                                    </tr>
                                </thead>
                                <tbody id="mealRows"></tbody>
                            </table>
                        </div>

                        <button type="submit" class="btn btn-success mt-3">Save Changes</button>
                        <a href="{{ route('mealplans.index') }}" class="btn btn-secondary mt-3">
                            Cancel
                        </a>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
    document.addEventListener('DOMContentLoaded', async function () {
        const inventoryItems = @json($inventoryItems); // all inventory
        const mealPlanData = @json($mealPlan->meals);  // existing meals
        const recipeList = @json($recipes);            // all recipes

        const weekStartInput = document.getElementById('week_start');
        const weekRangeSpan = document.getElementById('weekRange');
        const tbody = document.getElementById('mealRows');
        const weeklyPlan = document.getElementById('weeklyPlan');
        const mealTypes = ['breakfast','lunch','dinner','snack'];

        // Build lookup for existing meals
        const mealsLookup = mealPlanData.reduce((acc, meal) => {
            acc[`${meal.date}_${meal.meal_type}`] = meal;
            return acc;
        }, {});

        function formatDate(date) {
            return date.toISOString().split('T')[0];
        }

        // Generate ingredient row HTML
        function createIngredientRow(mealIndex, idx, itemId = '', qtyUsed = '') {
            const selectedItem = inventoryItems.find(i => i.id == itemId);
            const availableQty = selectedItem ? selectedItem.quantity : 0;
            const totalQty = selectedItem ? selectedItem.original_quantity : 0;

            const placeholder = availableQty ? `Max: ${availableQty} / Total: ${totalQty}` : 'Out of stock';

            // Build dropdown options (exclude zero-stock & expired)
            const optionsHtml = inventoryItems
                .filter(item => item.status !== 'expired' && item.quantity > 0)
                .sort((a,b) => new Date(a.expiry_date) - new Date(b.expiry_date))
                .map(item => {
                    const expiryLabel = item.expiry_date ? ` (Exp: ${new Date(item.expiry_date).toISOString().split('T')[0]})` : '';
                    const unitLabel = item.unit ? ` ${item.unit}` : '';
                    return `<option value="${item.id}" data-available="${item.quantity}" data-original="${item.original_quantity}" ${item.id==itemId?'selected':''}>
                                ${item.name}${expiryLabel}${unitLabel ? ' - ' + unitLabel : ''} [Available: ${item.quantity} / Total: ${item.original_quantity}]
                            </option>`;
                }).join('');

            return `
                <div class="mb-2 d-flex align-items-center mt-1">
                    <select name="meals[${mealIndex}][ingredients][${idx}][inventory_item_id]" 
                            class="form-select form-select-sm me-1 text-xs ingredient-select" style="width:60%">
                        <option value="">Select Ingredient</option>
                        ${optionsHtml}
                    </select>
                    <input type="number" name="meals[${mealIndex}][ingredients][${idx}][quantity_used]" 
                        class="form-control form-control-sm text-xs text-center qty-input" 
                        style="width:40%" value="${qtyUsed}" min="1" max="${availableQty}" 
                        placeholder="${placeholder}" ${availableQty===0 && itemId ? 'disabled' : ''}>
                    <button type="button" class="btn btn-sm btn-danger remove-ingredient ms-1">X</button>
                </div>
            `;
        }

        // Generate the weekly plan table
        function generatePlan(startDateString) {
            tbody.innerHTML = '';
            const startDate = new Date(startDateString);
            const endDate = new Date(startDate);
            endDate.setDate(startDate.getDate() + 6);
            weekRangeSpan.textContent = `${formatDate(startDate)} — ${formatDate(endDate)}`;

            const today = new Date();
            today.setHours(0,0,0,0);

            for(let i=0;i<7;i++){
                const current = new Date(startDate);
                current.setDate(startDate.getDate() + i);
                const dayName = current.toLocaleDateString('en-US', { weekday:'short' });
                const dateFormatted = formatDate(current);

                let row = `<tr><td><strong>${dayName}</strong><br><small>${dateFormatted}</small></td>`;

                mealTypes.forEach(type => {
                    const mealIndex = `${i}_${type}`;
                    const mealKey = `${dateFormatted}_${type}`;
                    const existingMeal = mealsLookup[mealKey];

                    // Determine recipe/custom
                    let recipeName = '';
                    let customName = '';
                    if (existingMeal && existingMeal.recipe_name) {
                        const foundRecipe = recipeList.find(r => r.name === existingMeal.recipe_name);
                        if (foundRecipe) recipeName = existingMeal.recipe_name;
                        else customName = existingMeal.recipe_name;
                    }

                    const ingredients = existingMeal && existingMeal.ingredients.length>0 ? existingMeal.ingredients : [];

                    // Build recipe options
                    let recipeOptions = `<option value="">-- Select Recipe --</option>`;
                    recipeList.forEach(r=>{
                        recipeOptions += `<option value="${r.name}" data-ingredients='${JSON.stringify(r.ingredients)}' ${r.name===recipeName?'selected':''}>${r.name}</option>`;
                    });

                    const isPast = current < today;

                    row += `<td class="p-1">
                        <input type="hidden" name="meals[${mealIndex}][date]" value="${dateFormatted}">
                        <input type="hidden" name="meals[${mealIndex}][meal_type]" value="${type}">
                        <input type="hidden" name="meals[${mealIndex}][recipe_name]" value="">
                        <input type="hidden" name="meals[${mealIndex}][custom_recipe_name]" value="">

                        <select name="meals[${mealIndex}][recipe_name]" 
                            class="form-select form-select-sm mb-2 text-xs recipe-select" 
                            data-meal-index="${mealIndex}" ${isPast?'disabled':''}>
                            ${recipeOptions}
                        </select>

                        <input type="text" name="meals[${mealIndex}][custom_recipe_name]" 
                            class="form-control form-control-sm mb-2 text-xs custom-recipe-input" 
                            placeholder="Custom Meal Plan (optional)" value="${customName}" ${isPast?'disabled':''}>

                        <div class="ingredient-list" data-meal-index="${mealIndex}">`;

                    ingredients.forEach((ing, idx)=>{
                        row += createIngredientRow(mealIndex, idx, ing.inventory_item_id ?? '', ing.quantity_used ?? '');
                    });

                    row += `<button type="button" class="btn btn-sm btn-outline-primary add-ingredient mt-1" 
                                data-meal-index="${mealIndex}" ${isPast?'disabled style="pointer-events:none; opacity:0.5;"':''}>
                                + Add Ingredient
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger reset-ingredients mt-1 ms-1" 
                                data-meal-index="${mealIndex}" ${isPast?'disabled':''}>
                                Reset
                            </button>
                        </div>
                    </td>`;
                });

                row += `</tr>`;
                tbody.insertAdjacentHTML('beforeend', row);
            }
        }

        // Add/remove ingredient dynamically
        weeklyPlan.addEventListener('click', function(e){
            if(e.target.classList.contains('add-ingredient')){
                const button = e.target;
                const mealIndex = button.dataset.mealIndex;
                const ingredientList = button.closest('.ingredient-list');
                const idx = ingredientList.querySelectorAll('.mb-2').length;
                button.insertAdjacentHTML('beforebegin', createIngredientRow(mealIndex, idx));
            }
            if(e.target.classList.contains('remove-ingredient')){
                e.target.closest('.mb-2').remove();
            }
            if (e.target.classList.contains('reset-ingredients')) {
                const ingredientList = e.target.closest('.ingredient-list');
                // Remove all ingredient input rows
                ingredientList.querySelectorAll('.mb-2').forEach(el => el.remove());

                const td = e.target.closest('td');
                if (td) {
                    // Clear recipe select
                    const recipeSelect = td.querySelector('.recipe-select');
                    if (recipeSelect) recipeSelect.value = '';

                    // Clear custom recipe input
                    const customInput = td.querySelector('.custom-recipe-input');
                    if (customInput) customInput.value = '';
                }
            }

        });

        // Update ingredient quantity placeholder dynamically
        weeklyPlan.addEventListener('change', function(e){
            if(e.target.classList.contains('ingredient-select')){
                const select = e.target;
                const selectedOption = select.selectedOptions[0];
                const qtyInput = select.closest('.d-flex').querySelector('.qty-input');
                const available = parseFloat(selectedOption.dataset.available || 0);
                const total = parseFloat(selectedOption.dataset.original || 0);
                qtyInput.max = available;
                qtyInput.placeholder = available ? `Max: ${available} / Total: ${total}` : 'Out of stock';
                qtyInput.disabled = !available;
            }
        });

        // Handle recipe/custom conflicts & autofill
        weeklyPlan.addEventListener('change', async function(e){
            const td = e.target.closest('td'); if(!td) return;
            const mealIndex = td.querySelector('.recipe-select')?.dataset.mealIndex;

            if(e.target.classList.contains('recipe-select')){
                const select = e.target;
                const customInput = td.querySelector('.custom-recipe-input');

                if(customInput && customInput.value.trim()!==''){
                    const result = await Swal.fire({
                        title: "Replace Custom Meal?",
                        text: "Selecting a recipe will remove custom meal. Continue?",
                        icon: "warning",
                        showCancelButton:true,
                        confirmButtonText:"Yes, replace",
                        cancelButtonText:"Cancel"
                    });
                    if(result.isConfirmed) customInput.value=''; else { select.value=''; return; }
                }

                const ingredientList = td.querySelector('.ingredient-list');
                ingredientList.querySelectorAll('.mb-2').forEach(el=>el.remove());

                const selectedOption = select.selectedOptions[0];
                const ingredientsData = selectedOption.dataset.ingredients ? JSON.parse(selectedOption.dataset.ingredients) : [];

                const insufficient = ingredientsData.some(ing => {
                    const matchingItems = inventoryItems
                        .filter(i => i.name === ing.name && i.status !== 'expired' && i.quantity > 0);

                    const totalAvailable = matchingItems.reduce((sum, item) => sum + Number(item.quantity), 0);

                    return totalAvailable < Number(ing.quantity_used || 1);
                });

                if (insufficient) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Insufficient Ingredients',
                        text: 'Recipe cannot be added due to low stock.'
                    });
                    select.value = '';
                    return;
                }

                ingredientsData.forEach((ing, idx)=>{
                    // Find ALL inventory items with the same name and not expired
                    const matchingItems = inventoryItems
                        .filter(i => i.name === ing.name && i.status !== 'expired' && i.quantity > 0)
                        .sort((a,b) => new Date(a.expiry_date) - new Date(b.expiry_date)); // earliest expiry first

                    // Choose the best usable item
                    // 1. item that has enough quantity
                    // 2. otherwise fallback to highest available quantity but > 0
                    let selectedItem = matchingItems.find(i => i.quantity >= ing.quantity_used);

                    if (!selectedItem) {
                        // fallback: choose the one with the most quantity
                        selectedItem = matchingItems.length ? matchingItems[0] : null;
                    }

                    const itemId = selectedItem ? selectedItem.id : '';
                    const qtyUsed = ing.quantity_used ?? '';
                    ingredientList.insertAdjacentHTML('beforeend', createIngredientRow(mealIndex, idx, itemId, qtyUsed));
                });
            }

            

            // Quantity enforcement
            if(e.target.matches('input[type="number"]')){
                const input = e.target;
                const max = parseFloat(input.max);
                if(max && input.value>max) Swal.fire({icon:'warning', title:'Quantity Limit Exceeded', text:`Max ${max}`}), input.value=max;
                else if(input.value<1) input.value=1;
            }
        });

        weeklyPlan.addEventListener('input', async function(e){
            if(e.target.classList.contains('custom-recipe-input')){
                const input = e.target;
                const td = input.closest('td');
                const recipeSelect = td.querySelector('.recipe-select');
                if(recipeSelect && recipeSelect.value.trim()!=='' && input.value.trim()!==''){
                    const result = await Swal.fire({
                        title:"Replace Recipe?",
                        text:"Typing a custom meal will remove selected recipe. Continue?",
                        icon:"warning",
                        showCancelButton:true,
                        confirmButtonText:"Yes, replace recipe",
                        cancelButtonText:"Cancel"
                    });
                    if(result.isConfirmed){
                        recipeSelect.value='';
                        td.querySelectorAll('.ingredient-list .mb-2').forEach(el=>el.remove());
                        Swal.fire({toast:true, position:'top-end', icon:'info', title:'Recipe cleared', showConfirmButton:false, timer:1200});
                    } else input.value='';
                }
            }
        });

        // Before submit: ensure custom recipe is selected
        document.querySelector('form').addEventListener('submit', function(){
            document.querySelectorAll('.custom-recipe-input').forEach(input=>{
                const td = input.closest('td');
                const recipeSelect = td.querySelector('.recipe-select');
                const customValue = input.value.trim();
                if(!recipeSelect.value && customValue!==''){
                    let opt = recipeSelect.querySelector(`option[value="${customValue}"]`);
                    if(!opt){ opt = document.createElement('option'); opt.value=customValue; opt.textContent=customValue; recipeSelect.appendChild(opt); }
                    opt.selected = true; recipeSelect.value = customValue;
                }
            });
        });

        if(weekStartInput.value) generatePlan(weekStartInput.value);
    });
    </script>


</x-app-layout>
