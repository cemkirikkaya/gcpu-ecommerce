import type { Metadata } from "next";
import { Cormorant_Garamond, DM_Sans } from "next/font/google";

import { AuthProvider } from "@/context/auth-context";
import { StockAlertProvider } from "@/context/stock-alert-context";
import { WishlistProvider } from "@/context/wishlist-context";
import { GoogleAuthProvider } from "@/components/auth/google-auth-provider";
import { SiteFooter } from "@/components/layout/site-footer";
import { SiteHeader } from "@/components/layout/site-header";

import "./globals.css";

const dmSans = DM_Sans({
  variable: "--font-dm-sans",
  subsets: ["latin"],
});

const cormorant = Cormorant_Garamond({
  variable: "--font-cormorant",
  subsets: ["latin"],
  weight: ["400", "500", "600", "700"],
});

export const metadata: Metadata = {
  title: {
    default: "GCPU",
    template: "%s · GCPU",
  },
  description: "GCPU — seçkin ürünler, sakin bir alışveriş deneyimi.",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="tr" className={`${dmSans.variable} ${cormorant.variable} h-full`}>
      <body className="min-h-full bg-background text-foreground antialiased">
        <GoogleAuthProvider>
          <AuthProvider>
            <WishlistProvider>
              <StockAlertProvider>
                <SiteHeader />
                <main className="flex-1">{children}</main>
                <SiteFooter />
              </StockAlertProvider>
            </WishlistProvider>
          </AuthProvider>
        </GoogleAuthProvider>
      </body>
    </html>
  );
}
