import { AdminOnlyGuard } from "@/components/admin/admin-only-guard";

import { NewPostClient } from "./new-post-client";

export default function AdminNewPostPage() {
  return (
    <AdminOnlyGuard>
      <NewPostClient />
    </AdminOnlyGuard>
  );
}
