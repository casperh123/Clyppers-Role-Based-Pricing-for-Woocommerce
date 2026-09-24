import { RoleService } from "@/services/roleService";
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
      <SelectControl
        label="Copy rules to"
        items={options}
        value={chosenItem}
        hideLabelFromVision={true}
        onValueChange={(item) => setChosenSlug(item?.value ?? null)}
        className="no-gap"
        size="default"
        />
        
      <Button onClick={() => setOpenModal(true)} variant="primary">Copy rules to</Button>

      { openModal && (
        <Modal onRequestClose={() => setOpenModal(false)} size="small">
          { isPending ? (
            <Spinner />
          ) : (
            <div>
              <h3>Copy rules to {chosenRole?.name}</h3>
              <p>This will erase all rules from {chosenRole?.name}, and the rules of {thisRole?.name} will be added to it.</p>

              <div className="row">
                <Button onClick={() => copyFromSlug()} variant="primary">Confirm</Button>
                <Button onClick={() => setOpenModal(false)} variant="secondary">Cancel</Button>
              </div>
            </div>
          )}
        </Modal>
      )}
    </div>
  );
}
