import { Role } from "@/types/role";
import { ApiPath } from "@/shared/apiPaths";
import apiFetch from "@wordpress/api-fetch";

export class RoleService {
  public static async getRoles(): Promise<Role[]> {
    return apiFetch<Role[]>({
      path: ApiPath.rolesPath(),
    });
  }

  public static async setRoleActive(role: Role, active: boolean): Promise<Role[]> {
    const updatedRole = {
      ...role,
      active,
    };

    await this.updateRole(updatedRole);

    return this.getRoles();
  }

  public static async updateRole(role: Role): Promise<number> {
    return apiFetch<number>({
      path: ApiPath.rolesPath(),
      method: "PATCH",
      data: role
    });
  }

  public static async copyFrom(fromSlug: string, toSlug: string): Promise<void> {
    return apiFetch({
      path: ApiPath.roleSlugCopyFromPath(toSlug),
      method: "POST",
      data: {
        copy_rule_slug: fromSlug
      }
    });
  }
}
