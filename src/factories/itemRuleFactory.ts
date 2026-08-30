import { Category } from "@/types/category";
import { ItemRule } from "@/types/ItemRule";
import { PricingRule } from "@/types/pricingRule";
import { Product } from "@/types/product";

export const createRuleFromProduct = (product: Product): ItemRule => {
  return {
    id: product.id,
    name: product.name,
    price_html: product.price_html,
    rule: itemRule(),
    min_qty: 0,
    image_url: product.image_url
  }
}

export const createRuleFromCategory = (category: Category): ItemRule => {
  return {
    id: category.id,
    name: category.name,
    rule: itemRule(),
    min_qty: 0,
    image_url: category.image.src
  }
}

const itemRule = (): PricingRule => {
  return {
    type: 'percent',
    value: '0',
    quantity: '0',
    quantity_type: 'percent' 
  };
}
