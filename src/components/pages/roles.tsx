import { RoleService } from "@/services/roleService";
import { __ } from "@wordpress/i18n";
import { Role } from "@/types/role";
import { useEffect, useState, useTransition } from "react";
import { Button, Spinner } from '@wordpress/components';
import { cog } from '@wordpress/icons';
import { useNavigate } from 'react-router-dom';
import { RoleCard } from "../cards/roleCard";


export function Roles() {
  const [allRoles, setAllRoles] = useState<Role[]>([]);
  const [isPending, setTransition] = useTransition();
  const navigate = useNavigate();

  useEffect(() => {
    setTransition(async () => {
      const roles = await RoleService.getRoles();

      setAllRoles(roles);
    });
  }, []);

  const setActiveStatus = async (role: Role, active: boolean) => {
    const roles = await RoleService.setRoleActive(role, active);
    setAllRoles(roles);
  }

  return (
    <div>
      <div className="row space-between roles-header">
        <h1>{__('Roles', 'clyppers-role-based-pricing-for-woocommerce')}</h1>
        <Button variant="secondary" icon={cog} onClick={() => navigate('/settings')}>
          {__('Settings', 'clyppers-role-based-pricing-for-woocommerce')}
        </Button>
      </div>
      <div className="roles-list">
        { isPending ? (
          <Spinner />
        ) : (
         allRoles.map(role => (
            <RoleCard key={role.slug} role={role} onRoleChanged={(async (role) => await setActiveStatus(role, !role.active))}/>
          ))
        )}  
      </div>
    </div>
  );
}
