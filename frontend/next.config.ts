import type { NextConfig } from "next";

const mediaBaseUrl =
  process.env.NEXT_PUBLIC_MEDIA_URL ??
  process.env.NEXT_PUBLIC_API_URL?.replace(/\/api\/?$/, "") ??
  "http://localhost";

let mediaPattern: URL;

try {
  mediaPattern = new URL(mediaBaseUrl);
} catch {
  mediaPattern = new URL("http://localhost");
}

const nextConfig: NextConfig = {
  images: {
    dangerouslyAllowLocalIP: true,
    remotePatterns: [
      {
        protocol: mediaPattern.protocol.replace(":", "") as "http" | "https",
        hostname: mediaPattern.hostname,
        ...(mediaPattern.port ? { port: mediaPattern.port } : {}),
        pathname: "/storage/**",
      },
      {
        protocol: "http",
        hostname: "localhost",
        port: "80",
        pathname: "/storage/**",
      },
      {
        protocol: "http",
        hostname: "127.0.0.1",
        pathname: "/storage/**",
      },
      {
        protocol: "http",
        hostname: "laravel.test",
        pathname: "/storage/**",
      },
      {
        protocol: "https",
        hostname: "laravel.test",
        pathname: "/storage/**",
      },
    ],
  },
  async rewrites() {
    return [
      {
        source: "/media/:path*",
        destination: `${mediaBaseUrl.replace(/\/$/, "")}/storage/:path*`,
      },
    ];
  },
};

export default nextConfig;
