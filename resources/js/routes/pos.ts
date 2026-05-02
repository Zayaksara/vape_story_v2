export default {
  dashboard: {
    index: {
      url: () => '/pos/dashboard',
    },
  },
  products: {
    index: {
      url: (params?: { category?: string; search?: string }) => {
        const query = new URLSearchParams();
        if (params?.category) query.set('category', params.category);
        if (params?.search) query.set('search', params.search);
        const queryString = query.toString();
        return `/pos/products${queryString ? `?${queryString}` : ''}`;
      },
    },
  },
  transactions: {
    today: {
      url: (params?: { date?: string }) => {
        const query = params?.date ? `?date=${params.date}` : '';
        return `/pos/transactions/today${query}`;
      },
      data: (params?: { date?: string }) => {
        const query = params?.date ? `?date=${params.date}` : '';
        return `/pos/transactions/today/data${query}`;
      },
    },
  },
}
