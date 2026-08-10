"use client";

import { GoogleLogin } from "@react-oauth/google";

type GoogleSignInButtonProps = {
  disabled?: boolean;
  onError: (message: string) => void;
  onSuccess: (idToken: string) => Promise<void>;
};

export function GoogleSignInButton({
  disabled = false,
  onError,
  onSuccess,
}: GoogleSignInButtonProps) {
  const clientId = process.env.NEXT_PUBLIC_GOOGLE_CLIENT_ID;

  if (!clientId) {
    return null;
  }

  return (
    <div className={disabled ? "pointer-events-none opacity-50" : undefined}>
      <GoogleLogin
        onSuccess={async (credentialResponse) => {
          const idToken = credentialResponse.credential;

          if (!idToken) {
            onError("Google ile giriş başarısız.");
            return;
          }

          try {
            await onSuccess(idToken);
          } catch (err) {
            onError(err instanceof Error ? err.message : "Google ile giriş başarısız.");
          }
        }}
        onError={() => onError("Google ile giriş başarısız.")}
        theme="outline"
        size="large"
        shape="pill"
        text="continue_with"
        width="100%"
      />
    </div>
  );
}
