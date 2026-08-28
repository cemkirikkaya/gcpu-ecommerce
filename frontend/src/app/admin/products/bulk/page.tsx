"use client";

import { useState } from "react";

import { Button, ButtonLink } from "@/components/ui/button";
import { useAuth } from "@/context/auth-context";
import { api } from "@/lib/api";
import type { BulkProductImportResult, BulkProductUpdateResult } from "@/lib/types";

type BulkResult = BulkProductImportResult | BulkProductUpdateResult;

function isImportResult(result: BulkResult): result is BulkProductImportResult {
  return "created" in result;
}

export default function AdminProductBulkPage() {
  const { token } = useAuth();
  const [importFile, setImportFile] = useState<File | null>(null);
  const [updateFile, setUpdateFile] = useState<File | null>(null);
  const [importing, setImporting] = useState(false);
  const [updating, setUpdating] = useState(false);
  const [importResult, setImportResult] = useState<BulkProductImportResult | null>(null);
  const [updateResult, setUpdateResult] = useState<BulkProductUpdateResult | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  async function handleDownloadTemplate(type: "import" | "update") {
    if (!token) {
      return;
    }

    setError(null);

    try {
      await api.adminDownloadProductBulkTemplate(token, type);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Şablon indirilemedi.");
    }
  }

  async function handleImport() {
    if (!token || !importFile) {
      return;
    }

    setImporting(true);
    setError(null);
    setMessage(null);
    setImportResult(null);

    try {
      const response = await api.adminBulkImportProducts(token, importFile);
      setImportResult(response.result);
      setMessage(response.message);
      setImportFile(null);
    } catch (err) {
      setError(err instanceof Error ? err.message : "CSV içe aktarılamadı.");
    } finally {
      setImporting(false);
    }
  }

  async function handleUpdate() {
    if (!token || !updateFile) {
      return;
    }

    setUpdating(true);
    setError(null);
    setMessage(null);
    setUpdateResult(null);

    try {
      const response = await api.adminBulkUpdateProducts(token, updateFile);
      setUpdateResult(response.result);
      setMessage(response.message);
      setUpdateFile(null);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Toplu güncelleme yapılamadı.");
    } finally {
      setUpdating(false);
    }
  }

  return (
    <div>
      <LinkBack />

      <p className="text-xs uppercase tracking-[0.35em] text-muted">Ürünler</p>
      <h1 className="mt-3 font-display text-4xl font-semibold">Toplu Ürün İşlemleri</h1>
      <p className="mt-3 max-w-3xl text-sm text-muted">
        CSV dosyası ile yeni ürün ekleyebilir veya mevcut varyantların fiyat ve stok bilgilerini
        SKU üzerinden toplu güncelleyebilirsiniz.
      </p>

      {error && <p className="mt-6 text-sm text-red-600">{error}</p>}
      {message && <p className="mt-6 text-sm text-muted">{message}</p>}

      <div className="mt-10 grid gap-6 xl:grid-cols-2">
        <BulkCard
          title="CSV ile Ürün İçe Aktar"
          description="Aynı ürün adına sahip satırlar tek ürün altında varyant olarak birleştirilir."
          columns="name, description, price, category, sku, stock, color, memory, model, size"
          file={importFile}
          onFileChange={setImportFile}
          onDownloadTemplate={() => void handleDownloadTemplate("import")}
          onSubmit={() => void handleImport()}
          submitting={importing}
          submitLabel="CSV İçe Aktar"
        />

        <BulkCard
          title="Toplu Fiyat / Stok Güncelle"
          description="Her satır bir SKU içermelidir. Fiyat ve stok alanlarından en az biri doldurulmalıdır."
          columns="sku, price, stock"
          file={updateFile}
          onFileChange={setUpdateFile}
          onDownloadTemplate={() => void handleDownloadTemplate("update")}
          onSubmit={() => void handleUpdate()}
          submitting={updating}
          submitLabel="Toplu Güncelle"
        />
      </div>

      {importResult && (
        <ResultPanel title="İçe Aktarma Sonucu" result={importResult} />
      )}

      {updateResult && <ResultPanel title="Güncelleme Sonucu" result={updateResult} />}
    </div>
  );
}

function LinkBack() {
  return (
    <ButtonLink href="/admin/products" variant="secondary" className="mb-6">
      ← Ürünlere Dön
    </ButtonLink>
  );
}

function BulkCard({
  title,
  description,
  columns,
  file,
  onFileChange,
  onDownloadTemplate,
  onSubmit,
  submitting,
  submitLabel,
}: {
  title: string;
  description: string;
  columns: string;
  file: File | null;
  onFileChange: (file: File | null) => void;
  onDownloadTemplate: () => void;
  onSubmit: () => void;
  submitting: boolean;
  submitLabel: string;
}) {
  return (
    <div className="rounded-[1.5rem] border border-line bg-surface p-6">
      <h2 className="text-xl font-semibold">{title}</h2>
      <p className="mt-2 text-sm text-muted">{description}</p>
      <p className="mt-4 rounded-[1rem] bg-background px-4 py-3 font-mono text-xs text-muted">
        {columns}
      </p>

      <div className="mt-6 flex flex-wrap gap-3">
        <Button type="button" variant="secondary" onClick={onDownloadTemplate}>
          Şablon İndir
        </Button>
        <label className="inline-flex cursor-pointer items-center rounded-full border border-line bg-background px-5 py-3 text-sm font-medium transition hover:border-accent">
          {file ? file.name : "CSV Seç"}
          <input
            type="file"
            accept=".csv,text/csv,text/plain"
            className="hidden"
            onChange={(event) => onFileChange(event.target.files?.[0] ?? null)}
          />
        </label>
      </div>

      <Button
        type="button"
        className="mt-6"
        disabled={!file || submitting}
        onClick={onSubmit}
      >
        {submitting ? "İşleniyor..." : submitLabel}
      </Button>
    </div>
  );
}

function ResultPanel({ title, result }: { title: string; result: BulkResult }) {
  return (
    <div className="mt-10 rounded-[1.5rem] border border-line bg-surface p-6">
      <h2 className="font-display text-2xl font-semibold">{title}</h2>

      <div className="mt-4 flex flex-wrap gap-4 text-sm">
        {isImportResult(result) ? (
          <>
            <Stat label="Oluşturulan" value={result.created} />
            <Stat label="Birleştirilen" value={result.merged} />
            <Stat label="Atlanan" value={result.skipped} />
          </>
        ) : (
          <>
            <Stat label="Güncellenen" value={result.updated} />
            <Stat label="Atlanan" value={result.skipped} />
          </>
        )}
        <Stat label="Hata" value={result.errors.length} />
      </div>

      {result.errors.length > 0 && (
        <ul className="mt-6 space-y-2 text-sm text-red-700">
          {result.errors.map((item) => (
            <li key={`${item.row}-${item.message}`}>
              Satır {item.row}: {item.message}
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}

function Stat({ label, value }: { label: string; value: number }) {
  return (
    <div className="rounded-full border border-line bg-background px-4 py-2">
      <span className="text-muted">{label}: </span>
      <span className="font-medium">{value}</span>
    </div>
  );
}
