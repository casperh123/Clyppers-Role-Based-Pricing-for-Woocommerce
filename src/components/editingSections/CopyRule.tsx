import { RoleService } from "@/services/roleService";
import { __, sprintf } from "@wordpress/i18n";
import { Role } from "@/types/role";
import { Button, Modal } from "@wordpress/components";
import { SelectControl, Spinner } from "@wordpress/ui";
import { useEffect, useState, useTransition } from "react";

interface CopyRuleProps {
  roleSlug: string
}

export const CopyRule = ({
  roleSlug
}: CopyRuleProps) => {
  const [openModal, setOpenModal] = useState(false);
  const [roles, setRoles] = useState<Role[]>([]);
  const [chosenSlug, setChosenSlug] = useState<string | null>(null);
  const [isPending, setTransition] = useTransition();
  const options = roles.filter(r => roleSlug != r.slug)
                        .map(r => ({ label: r.name, value: r.slug}));


  useEffect(() => {
    const load = async () => {
      setRoles(await RoleService.getRoles());
    }

    load();
  }, [])

  const chosenItem = options.find(r => r.value == chosenSlug) ?? null;
  const chosenRole = roles.find(r => r.slug == chosenSlug);
  const thisRole = roles.find(r => r.slug == roleSlug);

  const copyFromSlug = () => {
    setTransition(async () => {
      if(!chosenSlug) {
        return;
      }

      await RoleService.copyFrom(roleSlug, chosenSlug);

      setOpenModal(false);
    });
  }

  return (
    <div className="row">
      <Button onClick={() => setOpenModal(true)} variant="primary" disabled={chosenSlug == null}>{__('Copy rules to', 'clyppers-role-based-pricing-for-woocommerce')}</Button>

      <SelectControl
        label={__('Copy rules to', 'clyppers-role-based-pricing-for-woocommerce')}
        items={options}
        value={chosenItem}
        hideLabelFromVision={true}
        onValueChange={(item) => setChosenSlug(item?.value ?? null)}
        className="no-gap"
        size="default"
        />
        
      { openModal && (
        <Modal onRequestClose={() => !isPending && setOpenModal(false)} size="small">
          { isPending ? (
            <Spinner />
          ) : (
            <div>
              <h3>{sprintf(
                /* translators: %s: name of the role the rules are copied to */
                __('Copy rules to %s', 'clyppers-role-based-pricing-for-woocommerce'),
                chosenRole?.name ?? ''
              )}</h3>
              <p>{sprintf(
                /* translators: 1: role whose rules are replaced, 2: role whose rules are copied */
                __('This will erase all rules from %1$s, and the rules of %2$s will be added to it.', 'clyppers-role-based-pricing-for-woocommerce'),
                chosenRole?.name ?? '',
                thisRole?.name ?? ''
              )}</p>

              <div className="row">
                <Button onClick={() => copyFromSlug()} variant="primary">{__('Confirm', 'clyppers-role-based-pricing-for-woocommerce')}</Button>
                <Button onClick={() => setOpenModal(false)} variant="secondary">{__('Cancel', 'clyppers-role-based-pricing-for-woocommerce')}</Button>
              </div>
            </div>
          )}
        </Modal>
      )}
    </div>
  );
}
