import { AdminGuard } from "@/components/admin/admin-guard";
import { AdminNav } from "@/components/admin/admin-nav";

export default function AdminLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <AdminGuard>
      <div className="mx-auto flex min-h-[80vh] max-w-7xl flex-col lg:flex-row">
        <AdminNav />
        <div className="flex-1 p-6 lg:p-10">{children}</div>
      </div>
    </AdminGuard>
  );
}
