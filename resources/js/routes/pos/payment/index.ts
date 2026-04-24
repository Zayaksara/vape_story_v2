import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\POS\ProcessPaymentController::process
 * @see app/Http/Controllers/POS/ProcessPaymentController.php:13
 * @route '/pos/payment/process'
 */
export const process = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: process.url(options),
    method: 'post',
})

process.definition = {
    methods: ["post"],
    url: '/pos/payment/process',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\POS\ProcessPaymentController::process
 * @see app/Http/Controllers/POS/ProcessPaymentController.php:13
 * @route '/pos/payment/process'
 */
process.url = (options?: RouteQueryOptions) => {
    return process.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\POS\ProcessPaymentController::process
 * @see app/Http/Controllers/POS/ProcessPaymentController.php:13
 * @route '/pos/payment/process'
 */
process.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: process.url(options),
    method: 'post',
})
const payment = {
    process: Object.assign(process, process),
}

export default payment