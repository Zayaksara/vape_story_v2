import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\POS\DashboardController::index
 * @see app/Http/Controllers/POS/DashboardController.php:12
 * @route '/pos/dashboard'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/pos/dashboard',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\POS\DashboardController::index
 * @see app/Http/Controllers/POS/DashboardController.php:12
 * @route '/pos/dashboard'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\POS\DashboardController::index
 * @see app/Http/Controllers/POS/DashboardController.php:12
 * @route '/pos/dashboard'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\POS\DashboardController::index
 * @see app/Http/Controllers/POS/DashboardController.php:12
 * @route '/pos/dashboard'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\POS\DashboardController::index
 * @see app/Http/Controllers/POS/DashboardController.php:12
 * @route '/pos/dashboard'
 */
    const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: index.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\POS\DashboardController::index
 * @see app/Http/Controllers/POS/DashboardController.php:12
 * @route '/pos/dashboard'
 */
        indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: index.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\POS\DashboardController::index
 * @see app/Http/Controllers/POS/DashboardController.php:12
 * @route '/pos/dashboard'
 */
        indexForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: index.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    index.form = indexForm
const dashboard = {
    index: Object.assign(index, index),
}

export default dashboard