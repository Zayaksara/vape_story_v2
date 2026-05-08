import dashboard from './dashboard'
import payment from './payment'
import products from './products'
import transactions from './transactions'
const pos = {
    dashboard: Object.assign(dashboard, dashboard),
payment: Object.assign(payment, payment),
products: Object.assign(products, products),
transactions: Object.assign(transactions, transactions),
}

export default pos