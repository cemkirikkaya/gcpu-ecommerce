import { AdminOnlyGuard } from "@/components/admin/admin-only-guard";

import { EditPostClient } from "./edit-post-client";

export default async function AdminEditPostPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;

  return (
    <AdminOnlyGuard>
      <EditPostClient postId={Number(id)} />
    </AdminOnlyGuard>
  );
}
