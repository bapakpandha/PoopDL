import Header from "../__components/Header";
import Footer from "../__components/Footer";
import Hero from "./_components/hero";

export default function Home() {
  return (
    <div className="relative flex min-h-screen flex-col">
      <Header />
      <main className="flex flex-1 flex-col items-center justify-center">
        <div>
          {/* Hero Section */}
          <Hero />

          {/* Features Section
          <Features />

          {/* How It Works Section */}
          {/* <HowItWorks /> */}

          {/* Testimonials Section */}
          {/* <Testimonials /> */}

          {/* FAQ Section */}
          {/* <FrequentlyAsked />  */}
        </div>
      </main>
      <Footer />
    </div>
  );
}
