import dashboard from './dashboard'
import products from './products'
import categories from './categories'
import transactions from './transactions'
import reports from './reports'
import users from './users'
import promotions from './promotions'
const admin = {
    dashboard: Object.assign(dashboard, dashboard),
products: Object.assign(products, products),
categories: Object.assign(categories, categories),
transactions: Object.assign(transactions, transactions),
reports: Object.assign(reports, reports),
users: Object.assign(users, users),
promotions: Object.assign(promotions, promotions),
}

export default admin