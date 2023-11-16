import Layout from '@/features/landing/components/Layout/Layout';
import { AboutVilnaSection } from '../AboutVilnaSection/AboutVilnaSection/AboutVilnaSection';
import { WhyWeSection } from '@/features/landing/components/WhyWeSection/WhyWeSection/WhyWeSection';
import {
  ForWhoSection,
} from '@/features/landing/components/ForWhoSection/ForWhoSection/ForWhoSection';
import UnlimitedIntegrationsSection
  from '@/features/landing/components/UnlimitedIntegrationsSection/UnlimitedIntegrationsSection/UnlimitedIntegrationsSection';
import SignUpSection from '@/features/landing/components/SignUpSection/SignUpSection/SignUpSection';

export default function Landing() {
  return <Layout>
    <AboutVilnaSection />
    <WhyWeSection />
    <ForWhoSection />
    <UnlimitedIntegrationsSection />
    <SignUpSection />
  </Layout>;
}
