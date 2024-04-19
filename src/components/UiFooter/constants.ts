import FacebookFooterIcon from '../../features/landing/assets/svg/social-icons/facebook.svg';
import GitHubFooterIcon from '../../features/landing/assets/svg/social-icons/github.svg';
import InstagramFooterIcon from '../../features/landing/assets/svg/social-icons/instagram.svg';
import LinkedinFooterIcon from '../../features/landing/assets/svg/social-icons/linked-in.svg';
import { SocialMedia } from '../../features/landing/types/social-media';

export const socialLinks: SocialMedia[] = [
  {
    id: 'Instagram-link',
    icon: InstagramFooterIcon,
    alt: 'footer.alt_images.instagram',
    linkHref: 'https://www.instagram.com/',
    ariaLabel: 'footer.aria_labels.instagram',
  },
  {
    id: 'GitHub-link',
    icon: GitHubFooterIcon,
    alt: 'footer.alt_images.github',
    linkHref: ' https://github.com/VilnaCRM-Org',
    ariaLabel: 'footer.aria_labels.github',
  },
  {
    id: 'Facebook-link',
    icon: FacebookFooterIcon,
    alt: 'footer.alt_images.facebook',
    linkHref: 'https://www.facebook.com/',
    ariaLabel: 'footer.aria_labels.facebook',
  },
  {
    id: 'Linkedin-link',
    icon: LinkedinFooterIcon,
    alt: 'footer.alt_images.linkedin',
    linkHref: 'https://www.linkedin.com/',
    ariaLabel: 'footer.aria_labels.linkedin',
  },
];
