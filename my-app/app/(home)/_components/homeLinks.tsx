export const homeSectionsId = {
    hero: "hero",
    features: "features",
    howItWorks: "how-it-works",
    testimonials: "testimonials",
    frequentlyAsked: "faq",
} as const;

export const homeLinks = {
    hero: "#" + homeSectionsId.hero,
    features: "#" + homeSectionsId.features,
    howItWorks: "#" + homeSectionsId.howItWorks,
    testimonials: "#" + homeSectionsId.testimonials,
    frequentlyAsked: "#" + homeSectionsId.frequentlyAsked,
} as const;
