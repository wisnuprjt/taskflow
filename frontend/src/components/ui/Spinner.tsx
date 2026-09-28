export function Spinner({ className = "h-6 w-6" }: { className?: string }) {
  return <span className={`inline-block animate-spin rounded-full border-2 border-current border-t-transparent ${className}`} aria-label="Loading" />;
}

export function PageLoader() {
  return (
    <div className="flex justify-center py-16 text-indigo-600">
      <Spinner className="h-8 w-8" />
    </div>
  );
}
