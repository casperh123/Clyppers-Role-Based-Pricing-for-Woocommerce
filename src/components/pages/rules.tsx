import { RuleService } from "@/services/ruleService";
import { __ } from "@wordpress/i18n";
import { RoleRules } from "@/types/roleRules";
import { useEffect, useState, useTransition } from "react";
import { useNavigate, useParams } from "react-router-dom";
import { Button, Icon, Spinner } from '@wordpress/components';
import { arrowLeft } from "@wordpress/icons";
import { FormProvider, useForm } from "react-hook-form";
import { RulesPanel } from "../editingSections/RulesPanel";
import { ProductService } from "@/services/productService";
import { createRuleFromCategory, createRuleFromProduct } from "@/factories/itemRuleFactory";
import { CategoryService } from "@/services/categoryService";
import { CopyRule } from "../editingSections/CopyRule";

export function Rules() {
  const { id } = useParams<{ id: string }>();
  const  numericId = id ? parseInt(id) : 0;
  const [rule, setRule] = useState<RoleRules>();
  const [isLoading, setIsLoading] = useState(true);
  const [isSaving, setTransition] = useTransition();
  const navigate = useNavigate();

  const methods = useForm<RoleRules>();
  const { isDirty } = methods.formState;

  useEffect(() => {
    const load = async () => {
      const rule: RoleRules = await RuleService.getRule(numericId);

      setRule(rule);
      methods.reset(rule);

      setIsLoading(false);
    }

    load();
  }, [numericId])
  
  const onSubmit = async (rule: RoleRules) => {
      setTransition(() => { 
        RuleService.updateRules(rule); 
        methods.reset(rule);
      });
  }

  if (isLoading || !rule) {
    return <Spinner />
  }

  const searchProducts = async (search: string) => {
    return ProductService.getProductsByName(search);
  }

  const searchCategories = async (search: string) => {
    return CategoryService.getCategoriesByName(search);
  }
  
  return(
    <div>
      <FormProvider {...methods}>
        <form onSubmit={methods.handleSubmit(onSubmit)}>
          <div className="row" style={{marginBottom: 40}}>
            <Button icon={arrowLeft} onClick={() => navigate(-1)} label={__('Back to roles', 'clyppers-role-based-pricing-for-woocommerce')}/>
            <h1>{rule?.role_name}</h1>
          </div>
          <div className="roles-list">
            <div className="row" style={{ justifyContent: "space-between", alignItems: "center"}}>
              <Button type="submit" variant={isDirty ? "primary" : "secondary"}  disabled={isSaving || !isDirty}>
                {isSaving ? __('Saving…', 'clyppers-role-based-pricing-for-woocommerce') : __('Save', 'clyppers-role-based-pricing-for-woocommerce')}
              </Button>
              <CopyRule roleSlug={rule.role_slug}/>
            </div>
            <RulesPanel 
              title={__('Product rules', 'clyppers-role-based-pricing-for-woocommerce')}
              createRule={(product) => createRuleFromProduct(product)}
              onSearch={searchProducts}
              ruleKey="products"
              startOpen={true}
              modalTitle={__('Add product', 'clyppers-role-based-pricing-for-woocommerce')}
              />
            <RulesPanel
              title={__('Category rules', 'clyppers-role-based-pricing-for-woocommerce')}
              createRule={(category) => createRuleFromCategory(category)}
              onSearch={searchCategories}
              ruleKey="single_categories" 
              modalTitle={__('Add category', 'clyppers-role-based-pricing-for-woocommerce')}
              />
          </div>
        </form>
      </FormProvider>
    </div>
  );
}
