export type NavItem = {
  label: string;
  link?: string;
  children?: NavItem[];
  iconImage?: string;
};

export const navItems: NavItem[] = [
  { 
    label: "Home", 
    link: "/" 
  },
  {label: "Tools",
  link: "#",
  children: [
    {
      label: "Home",
      link: "/",
    },
    {
      label: "Bulk Download",
      link: "/bulk",
    },
    {
      label: "History",
      link: "/history",
    }
  ]
  },
{
  label: "About Us",
    link: "#"
},
{
  label: "Contact Us",
    link: "#"
}
];


export const headerSections = {
  hero: "hero",
  features: "bulk",
  howItWorks: "how-it-works",
  testimonials: "testimonials",
  frequentlyAsked: "faq",
} as const;

export const headerLinks = {
  hero: "#" + headerSections.hero,
  features: "/" + headerSections.features,
  howItWorks: "#" + headerSections.howItWorks,
  testimonials: "#" + headerSections.testimonials,
  frequentlyAsked: "#" + headerSections.frequentlyAsked,
} as const;