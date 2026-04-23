declare module 'ifvisible.js' {
  interface IfVisible {
    on(event: string, callback: () => void): void;
    off(event: string, callback?: () => void): void;
    setIdleDuration(seconds: number): void;
    wakeup(): void;
  }

  const ifvisible: IfVisible;
  export = ifvisible;
}
