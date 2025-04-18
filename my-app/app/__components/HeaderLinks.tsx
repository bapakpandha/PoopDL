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
  {label: "Features",
  link: "#",
  children: [
    {
      label: "Home",
      link: "/",
      // iconImage: todoImage
    },
    {
      label: "Bulk",
      link: "/bulk",
    },
    {
      label: "History",
      link: "/history",
    },
    {
      label: "Planning",
      link: "#",
    }
  ]
  },
{
  label: "Compnay",
    link: "#",
      children: [
        {
          label: "History",
          link: "history",
        },
        {
          label: "Our Team",
          link: "#"
        },
        {
          label: "Blog",
          link: "#"
        }
      ]
},
{
  label: "Careers",
    link: "#"
},
{
  label: "About",
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