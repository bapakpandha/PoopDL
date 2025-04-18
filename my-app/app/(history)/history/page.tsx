import Header from "../../__components/Header";
import Footer from "../../__components/Footer";

export default function History() {
  return (
    <div className="relative flex min-h-screen flex-col">
      <Header />
      <main className="flex flex-1 flex-col items-center justify-center p-24">
      <h1 className="text-4xl font-bold">Welcome to History</h1>
      <p className="mt-4 text-lg">This is a sample History Next.js application.</p>
      </main>
      <Footer />
    </div>
  );
}
