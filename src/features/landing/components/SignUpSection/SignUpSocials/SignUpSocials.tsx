import { SocialLink } from '@/components/ui/SocialLink/SocialLink';
import { ISocialLink } from '@/features/landing/types/social/types';

export function SignUpSocials({ socialLinks }: {
  socialLinks: ISocialLink[]
}) {
  return (
    <>
      {
        socialLinks.map(socialLink => <SocialLink key={socialLink.id} linkHref={socialLink.linkHref}
                                                  icon={socialLink.icon} title={socialLink.title} />)
      }

    </>
  );
}
