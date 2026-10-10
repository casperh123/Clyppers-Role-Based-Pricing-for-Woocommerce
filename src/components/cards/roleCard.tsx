import { Role } from "@/types/role";
import { __, _n, sprintf } from "@wordpress/i18n";
import { Button, Card, CardBody } from "@wordpress/components";
import { Badge } from "@wordpress/ui";
import { useNavigate } from "react-router-dom";
import { RoleStatus } from "../feedback/roleStatus";
import { useTransition } from "react";

interface RoleCardProps {
  role: Role,
  onRoleChanged: (role: Role) => Promise<void>
}

export const RoleCard = ({
  role,
  onRoleChanged
}: RoleCardProps) => {
  const navigate = useNavigate();
  const [isPending, startTransition] = useTransition();

  const handleRoleChange = () => {
    startTransition(async () => {
      await onRoleChanged(role);
    });
  }

  return (
    <Card className="row-card">
      <CardBody className="row-card-body">
        <div className="row space-between card-text">
          <div className="row">
            <RoleStatus active={role.active} />
            <span>{role.name}</span>
          </div>
          <Badge intent="draft">
            {sprintf(
              /* translators: %d: number of pricing rules */
              _n('%d rule', '%d rules', role.rule_count, 'clyppers-role-based-pricing-for-woocommerce'),
              role.rule_count
            )}
          </Badge>
        </div>

        <div className="row">
          { role.active ? (<>
            <Button onClick={() => navigate(`/role/${role.id}`)} variant="secondary" disabled={isPending} isBusy={isPending}>{__('Edit', 'clyppers-role-based-pricing-for-woocommerce')}</Button>
            <Button isDestructive onClick={handleRoleChange} disabled={isPending} isBusy={isPending}>{__('Disable', 'clyppers-role-based-pricing-for-woocommerce')}</Button>
          </>) : (
            <Button variant="primary" onClick={handleRoleChange} disabled={isPending} isBusy={isPending}>{__('Activate', 'clyppers-role-based-pricing-for-woocommerce')}</Button>
          )}
        </div>
      </CardBody>
    </Card>
  );
}
