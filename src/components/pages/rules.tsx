import { RuleService } from "@/services/ruleService";
import { __ } from "@wordpress/i18n";
import { RoleRules } from "@/types/roleRules";
import { useEffect, useState } from "react";
import { useNavigate, useParams } from "react-router-dom";
import { Button, Notice, Spinner } from '@wordpress/components';
import { arrowLeft } from "@wordpress/icons";
import { FormProvider, useForm } from "react-hook-form";
import { RulesPanel } from "../editingSections/RulesPanel";
import { ProductService } from "@/services/productService";
import { createRuleFromCategory, createRuleFromProduct } from "@/factories/itemRuleFactory";
import { CategoryService } from "@/services/categoryService";
import { CopyRule } from "../editingSections/CopyRule";

export function Rules() {
  const { id } = useParams<{ id: string }>();
  const numericId = id ? Number.parseInt(id, 10) : 0;
  const [rule, setRule] = useState<RoleRules>();
  const [error, setError] = useState<string>();
  const navigate = useNavigate();

  const methods = useForm<RoleRules>();
  // isSubmitting stays true until onSubmit's promise settles.
  const { isDirty, isSubmitting } = methods.formState;

  useEffect(() => {
    const load = async () => {
      try {
        const rule = await RuleService.getRule(numericId);
        setRule(rule);
        methods.reset(rule);
      } catch {
        setError(__('These pricing rules could not be loaded. Go back and try again.', 'clyppers-role-based-pricing-for-woocommerce'));
      }
    };

    void load();
  }, [numericId]);

  const onSubmit = async (rule: RoleRules) => {
    setError(undefined);

    try {
      await RuleService.updateRules(rule);
      // Only now is the form "saved"; on failure it stays dirty so the edits can be saved again.
      methods.reset(rule);
    } catch {
      setError(__('Your changes could not be saved. They are still here, so you can try again.', 'clyppers-role-based-pricing-for-woocommerce'));
    }
  };

  const errorNotice = error && (
    <Notice status="error" onRemove={() => setError(undefined)}>{error}</Notice>
  );

  if (!rule) {
    return errorNotice || <Spinner />;
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
        <form onSubmit={(event) => void methods.handleSubmit(onSubmit)(event)}>
          <div className="row" style={{marginBottom: 40}}>
            <Button icon={arrowLeft} onClick={() => navigate(-1)} label={__('Back to roles', 'clyppers-role-based-pricing-for-woocommerce')}/>
            <h1>{rule?.role_name}</h1>
          </div>
          <div className="roles-list">
            {errorNotice}
            <div className="row" style={{ justifyContent: "space-between", alignItems: "center"}}>
              <Button type="submit" variant={isDirty ? "primary" : "secondary"} disabled={isSubmitting || !isDirty} isBusy={isSubmitting}>
                {isSubmitting ? __('Saving…', 'clyppers-role-based-pricing-for-woocommerce') : __('Save', 'clyppers-role-based-pricing-for-woocommerce')}
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
