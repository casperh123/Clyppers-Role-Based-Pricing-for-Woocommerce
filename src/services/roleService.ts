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
    role.active = active;

    await this.updateRole(role);

    return this.getRoles();
  }

  public static async updateRole(role: Role): Promise<number> {
    return apiFetch<number>({
      path: ApiPath.rolesPath(),
      method: "PATCH",
      data: role
    });
  }
}
