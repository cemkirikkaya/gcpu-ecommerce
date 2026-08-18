import { ResetPasswordClient } from "./reset-password-client";

export default async function ResetPasswordPage({
  searchParams,
}: {
  searchParams: Promise<{ token?: string; email?: string }>;
}) {
  const { token = "", email = "" } = await searchParams;

  return <ResetPasswordClient token={token} email={decodeURIComponent(email)} />;
}
