import React from 'react';
import Switch from './sky-toggle';

export default function Demo() {
  return (
    <div className="flex min-h-screen items-center justify-center p-6 bg-slate-100 dark:bg-slate-900 transition-colors">
      <div className="flex flex-col items-center gap-4">
        <h2 className="text-xl font-semibold text-slate-800 dark:text-slate-100">
          Animated Sky Theme Toggle
        </h2>
        <Switch />
      </div>
    </div>
  );
}
