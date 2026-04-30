// Fixture data for POS e2e tests

export interface POSProduct {
  id: string;
  name: string;
  sku: string;
  price: number;
  stock: number;
  category_id: number;
  category_name: string;
  image_url?: string;
}

export interface POSCategory {
  id: number;
  name: string;
  product_count: number;
}

export interface POSData {
  products: POSProduct[];
  categories: POSCategory[];
}

export const mockPOSData: POSData = {
  categories: [
    { id: 1, name: 'Semua', product_count: 8 },
    { id: 2, name: 'Freebase', product_count: 4 },
    { id: 3, name: 'Nicotine Salt', product_count: 4 },
    { id: 4, name: 'Pod Device', product_count: 2 },
  ],
  products: [
    {
      id: '1',
      name: 'Freebase Blueberry Ice',
      sku: 'FB-001',
      price: 50000,
      stock: 50,
      category_id: 2,
      category_name: 'Freebase',
      image_url: '/images/products/freebase-blueberry.png',
    },
    {
      id: '2',
      name: 'Freebase Mango Peach',
      sku: 'FB-002',
      price: 50000,
      stock: 45,
      category_id: 2,
      category_name: 'Freebase',
      image_url: '/images/products/freebase-mango.png',
    },
    {
      id: '3',
      name: 'Freebase Strawberry Kiwi',
      sku: 'FB-003',
      price: 50000,
      stock: 40,
      category_id: 2,
      category_name: 'Freebase',
      image_url: '/images/products/freebase-strawberry.png',
    },
    {
      id: '4',
      name: 'Freebase Grape Mint',
      sku: 'FB-004',
      price: 50000,
      stock: 35,
      category_id: 2,
      category_name: 'Freebase',
      image_url: '/images/products/freebase-grape.png',
    },
    {
      id: '5',
      name: 'Nicotine Salt Lush Ice',
      sku: 'NS-001',
      price: 55000,
      stock: 60,
      category_id: 3,
      category_name: 'Nicotine Salt',
      image_url: '/images/products/ns-lush.png',
    },
    {
      id: '6',
      name: 'Nicotine Salt Cool Mint',
      sku: 'NS-002',
      price: 55000,
      stock: 55,
      category_id: 3,
      category_name: 'Nicotine Salt',
      image_url: '/images/products/ns-cool.png',
    },
    {
      id: '7',
      name: 'Nicotine Salt Tropical',
      sku: 'NS-003',
      price: 55000,
      stock: 50,
      category_id: 3,
      category_name: 'Nicotine Salt',
      image_url: '/images/products/ns-tropical.png',
    },
    {
      id: '8',
      name: 'Nicotine Salt Citrus',
      sku: 'NS-004',
      price: 55000,
      stock: 45,
      category_id: 3,
      category_name: 'Nicotine Salt',
      image_url: '/images/products/ns-citrus.png',
    },
  ],
};
